<?php

class Foxrate_Sdk_FoxrateRCI_Review extends Foxrate_Sdk_FoxrateRCI_Settings
{
    
    const ITERATION_FLUSH_COUNT = 50;

    const EMPTY_REVIEWS = true;

    public function __construct(
        Foxrate_Sdk_FoxrateRCI_ConfigInterface $config,
        Foxrate_Sdk_ApiBundle_Controllers_Authenticator $connector,
        Foxrate_Sdk_FoxrateRCI_DataManager $dataManager,
        Foxrate_Sdk_FoxrateRCI_ProductInterface $shopProduct,
        Foxrate_Sdk_ApiBundle_Resources_ApiEnvironmentInterface $environment,
        $client
    )
    {
        $this->config = $config;
        $this->foxrateConnector = $connector;
        $this->dataManager = $dataManager;
        $this->shopProduct = $shopProduct;
        $this->environment = $environment;
        $this->client = $client;

        $this->setSettings();
    }

    public function useDbCompatibleMode()
    {
        return false;
    }


    public function findProductsIdsWithReviews()
    {
        $this->loadSellerId_Cache();
        $reviews = $this->callProductsWithReviews()->reviews;
        return $this->convertReviewsCallToProductIds($reviews);
    }

    public function callProductsWithReviews()
    {
        $apiCall = $this->apiCallBuilder("reviews", "json");
        $result = $this->client->makeCall($apiCall);

        if ($result === null) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                'No result returned',
                FOX_CALL_PROD_NO_RESULT
            );
        }

        return $result;
    }

    /**
     * Get general review info about a single product
     *
     * @param $sProductId
     * @return array
     */
    public function getReviewTotalDataById($sProductId)
    {
        $objData = $this->dataManager->loadProductsRevsGeneral_Cache($sProductId);
        $this->checkReviewValid($objData);
        $generalRevInfo = $this->convertObjectToArray($objData);
        $generalRevInfo = $this->sortReviewCounts($generalRevInfo);

        return $generalRevInfo;
    }

    /**
     * General review info about a single product
     *
     * @param $sProductId
     * @return array
     */
    public function getFoxrateCategoryReviews($sProductId)
    {
        $errors = $this->getCategoryErrorMap();

        try {
            $objData = $this->dataManager->loadProductsRevsGeneral_Cache($sProductId);
            $generalRevInfo = $this->convertObjectToArray($objData);
            $generalRevInfo = $this->sortReviewCounts($generalRevInfo);
        } catch (Foxrate_Sdk_ApiBundle_Exception_ReviewsNotFoundException $e) {
            $errors['Catalog_display'] = 'false';
            return $errors;
        }
        $generalRevInfo = array_merge($generalRevInfo, $errors);
        return $generalRevInfo;
    }

    /**
     * Checks if given review is valid
     * @param $review
     * @throws Foxrate_Sdk_ApiBundle_Exception_ReviewsNotFoundException
     */
    protected function checkReviewValid($review)
    {
        if ($review->count == 0) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ReviewsNotFoundException (
                'Reviews for this product are not found.',
                FOX_CHECK_VALID_REVIEW
            );
        }
    }

    /**
     * Performs product review import from Foxrate using API
     * Loads product reviews from Foxrate and saves them to temporary directory, when all reviews are loaded, they are moved
     * to permanent directory, and temp is cleared. Failsafe strategy is used: If downloading the files fails, the old cache is used (instead of nothing)
     *
     * @return string
     */
    public function importProductReviews()
    {
        $cacheExpired = $this->hasCacheExpired();

        if ($cacheExpired) {
            try {
                $this->checkUserExist();
                $allProductIds =   $this->getProductIds();
                $productWithReviewsIds = $this->findProductsIdsWithReviews();

                $existingProductsWithReviews = $this->findExistingProductsIds($allProductIds, $productWithReviewsIds);
                $this->getSaveProductsReviews($existingProductsWithReviews, true);

                $productIds = $this->findLeftProductsIds($allProductIds, $existingProductsWithReviews);
                $this->getSaveProductsReviews($productIds, false, self::EMPTY_REVIEWS);

            } catch (Foxrate_Sdk_ApiBundle_Exception_ModuleException $e) {
                // 0 - means that we got error in output
                echo "1";
                return $this->config->writeToLog("Error: " . $e->getMessage());
            }
            $this->updateCacheImportDate();
            // 200 - means that 
            echo "200";
            return $this->config->writeToLog("Status: Imported Reviews Successfully");
        }
        // -1 - means that update is not needed
        echo "-1";
        return $this->config->writeToLog("Status: Import is not needed, cached reviews have not expired");
    }

    /**
     * Fi
     */
    public function findExistingProductsIds($allProductIds, $productWithReviewsIds)
    {
        return array_intersect($allProductIds, $productWithReviewsIds);
    }

    /**
     * Fi
     */
    public function findLeftProductsIds($allProductIds, $productWithReviewsIds)
    {
        return array_diff($allProductIds, $productWithReviewsIds);
    }

    /**
     * Performs reviews import for single product, by checking if reviews are expired by file time modified,
     * locks the update and starts import. Locks so other users wont starts the import again when it is started.
     * Lock has a timeout, if something breaks, the import can be started again when the locks expires.
     */
    public function cacheOnDemandSingleProductReview($productId)
    {
        $isExpired = $this->hasCacheExpired_CacheDemand($productId);
        $isLocked = $this->dataManager->isCacheLocked_CacheDemand($productId);
        if (!$isExpired) {
            return "Cache On demand for this product has not expired yet. Product ID: " . $productId;
        }
        if ($isLocked) {
            return "Cache import is already in progress for product: " . $productId;
        }
        try {
            $this->dataManager->lockOnDemandCacheProduct($productId);
            $prodId = array(array($productId));
            $this->getSaveProductsReviews($prodId, false);
            $this->dataManager->unlockOnDemandCacheProduct($productId);
        } catch (Foxrate_Sdk_ApiBundle_Exception_ModuleException $ex) {
            $this->dataManager->unlockOnDemandCacheProduct($productId);
            return $this->config->writeToLog("Error: " . $ex->getMessage());
        }
        return "Cache On Demand Import was successfull for product: " . $productId;
    }

    /**
     * Updates date when last import finished successfully
     */
    protected function updateCacheImportDate()
    {
        $this->config->saveShopConfVar($this->sFoxrateConfNameImportDate, strtotime("now"), "string");
    }

    /**
     * Gets product ID's, on failure throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     * @return Object
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function getProductIds()
    {
        $productIds = $this->shopProduct->getProductsIds();

        if (!$productIds) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "No products were found in database",
                FOX_GET_PROD_IDS_NO_DB_PROD
            );
        }
        return $productIds;
    }

    /**
     * Downloads product reviews from Foxrate and saves them in cache's temp directory
     * @param $productIds
     * @param $fullImport
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function getSaveProductsReviews($productIds, $fullImport, $empty = false)
    {
        $storeTempCache = false;
        try {
            $this->createDir($this->config->getCachedReviewsPathTemp());
        } catch (Foxrate_Sdk_ApiBundle_Exception_ModuleException $e) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                $e->getMessage() . '. Please make sure your cache dir is writable.',
                FOX_SAVE_PROD_REV_CACHE
            );
        }

        if ($fullImport) {
            $this->deleteDirContents($this->config->getCachedReviewsPathTemp());
            $storeTempCache = true;
        }
        $this->loadSellerId_Cache();
        $this->loadShopId_Cache();
        $i = 0;
        foreach ($productIds as $productId) {
            $productId = is_array($productId) ? $productId[0] : $productId;
            try {
                if(!$empty) {
                    $page = 0;
                    do {
                        $page++;
                        $this->sFoxrateSettings = array_merge($this->sFoxrateSettings, array("foxratePR_Page" => $page));
                        $productRev = $this->requestSingleProductReview_Foxrate($productId);
                        $status = isset($productRev->status) ? $productRev->status != "error" : true;

                        if ($status && $productRev->reviews_count != 0) {
                            $this->storeToCache(
                                $productId . ".page" . $productRev->current_page,
                                json_encode($productRev),
                                $storeTempCache
                            );
                        }
                    } while (isset($productRev->reviews_count) && ($productRev->pages_count != $productRev->current_page) && ($productRev->reviews_count != 0));

                    $productRevGen = $this->requestSingleProductReviewGeneral_Foxrate($productId);
                }
                else {
                    $productRevGen = new stdClass();
                    $productRevGen->count = 0;
                    $productRevGen->status = false;
                }

                $genStatus = isset($productRevGen->status) ? $productRevGen->status != "error" : true;
                if (!$genStatus || $productRevGen->count == 0) {
                    $productRevGen = array('count' => '0');
                }

                $this->storeToCache($productId . ".gener", json_encode($productRevGen), $storeTempCache);

            } catch (Foxrate_Sdk_ApiBundle_Exception_ModuleException $e) {
                $this->config->writeToLog(
                    "Product reviews with id " . $productId . " not imported. " . $e->getMessage()
                );
            }
        }

        $this->sFoxrateSettings = array_merge($this->sFoxrateSettings, array("foxratePR_Page" => 1));
        if ($fullImport) {
            $this->moveCacheTempToPermanent();
        }
    }

    /**
     * @return array
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     * @throws Foxrate_Sdk_ApiBundle_Exception_ReviewsNotFoundException
     */
    public function convertReviewsCallToProductIds($reviews)
    {


        if ($reviews === null) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ReviewsNotFoundException(
                'No result returned',
                FOX_CONVERT_REV_TO_PROD_IDS_NO_RESULTS
            );
        }

        $productIds = array();
        foreach ($reviews as $productId => $review) {
            $productIds[] = $productId;
        }

        return $productIds;
    }

    protected function moveCacheTempToPermanentNoDelete() {
        $this->copyDirContents($this->config->getCachedReviewsPathTemp(), $this->config->getCachedReviewsPath());
        $this->deleteDirContents($this->config->getCachedReviewsPathTemp());
    }

    /**
     * Move contents from temporary cache to permanent
     * @return null
     */
    protected function moveCacheTempToPermanent()
    {
        $this->deleteDirContents($this->config->getCachedReviewsPath());
        $this->copyDirContents($this->config->getCachedReviewsPathTemp(), $this->config->getCachedReviewsPath());
        $this->deleteDirContents($this->config->getCachedReviewsPathTemp());
    }

    /**
     * Copies contents from source to destination directory
     * @param $source
     * @param $destination
     *
     * @return bool
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function copyDirContents($source, $destination)
    {
        $success = true;
        if (!is_dir($source)) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "Directory copying failed, source directory does not exist: " . $source,
                FOX_COPY_DIR_CONTENT_NOT_EXIST
            );
        }
        $this->createDir($destination);
        $fileList = scandir($source);
        foreach ($fileList as $singleFile) {

            $sourcePath = $source . "/" . $singleFile;
            $destinPath = $destination . "/" . $singleFile;

            if (!is_dir($sourcePath)) {
                $result = copy($sourcePath, $destinPath);
                if (!$result && $singleFile != "." && $singleFile != "..") {
                    $this->config->writeToLog(
                        "Warning: Failed to copy file from : '" . $sourcePath . "' to '" . $destinPath . "'"
                    );
                    $success = false;
                }
            }
        }
        return $success;
    }


    /**
     * Deletes contents (files only) from given directory
     * @param string $dir
     * @return null
     */
    protected function deleteDirContents($dir)
    {
        $fileList = scandir($dir);
        $success = true;
        foreach ($fileList as $singleFile) {
            $path = $dir . "/" . $singleFile;

            if (!is_dir($path)) {
                $result = @unlink($path);
                if (!$result && $singleFile != "." && $singleFile != ".." && $singleFile != $this->config->getCachedReviewsPath()
                ) {
                    $this->config->writeToLog("Warning: Failed to remove file: " . $path);
                    $success = false;
                }
            };

        }
        return $success;
    }


    /**
     * Creates directory if does not exist
     * @param $dir
     */
    protected function createDir($dir)
    {
        $res = true;
        if (!is_dir($dir)) {
            $res = $this->recursive_mkdir($dir, 0777, true);
        }
        if (!$res) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "Failed to create directory structure: " . $dir
            );
        }
    }

    /**
     * Make a recursive dir from $path
     * We must check from document_root , as some user are using shared hosting,
     * and could have open base dir restriction
     *
     * @param $path
     * @param int $mode
     *
     * @return bool
     */
    function recursive_mkdir($path, $mode = 0777)
    {
        $websiteRoot = $_SERVER["DOCUMENT_ROOT"];
        $explodePath =  str_replace($websiteRoot, '', $path);
        $dirs = explode(DIRECTORY_SEPARATOR, $explodePath);
        $count = count($dirs);
        $path = $websiteRoot;
        for ($i = 0; $i < $count; ++$i) {
            if (!$dirs[$i]) {
                continue;
            }
            $path .= DIRECTORY_SEPARATOR . $dirs[$i];
            if (!is_dir($path) && !@mkdir($path, $mode)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Stores given data to temp or main cache
     * @param $name
     * @param $data
     * @param $isMainCacheStorage
     * @param string $format
     */
    protected function storeToCache($name, $data, $isTempCacheStorage, $format = "json")
    {
        if ($isTempCacheStorage) {
            $baseCachePath = $this->config->getCachedReviewsPathTemp();
        } else {
            $baseCachePath = $this->config->getCachedReviewsPath();
        }

        $pathName = $baseCachePath . "/" . $name . "." . $format;
        $saveResponse = file_put_contents($pathName, $data);
        if (!$saveResponse) {
            $this->config->writeToLog("Warning: Couldn't save data to temp cache directory: " . $pathName);
        }
    }


    /**
     * Gets single product review, builds api call, then uses it on curl with basic auth.
     * @param $productId
     * @return mixed
     */
    protected function requestSingleProductReview_Foxrate($productId)
    {
        $params = array("productId" => $productId);
        $apiCall = $this->apiCallBuilder("productReviews", "json", $params);
        $result = $this->makeRequestBasicAuth($apiCall, $this->sFoxrateAPIUsername, $this->sFoxrateAPIPassword);

        if ($result === null) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                'No result returned for product id: ' . $productId,
                FOX_REQ_SINGLE_PROD_REV
            );
        }

        return $result;
    }

    /**
     * Gets single product general review information, builds api call, then uses it on curl with basic auth.
     * @param $productId
     * @return mixed
     */
    protected function requestSingleProductReviewGeneral_Foxrate($productId)
    {
        $params = array("productId" => $productId);
        $apiCall = $this->apiCallBuilder("productGeneral", "json", $params);
        return $this->makeRequestBasicAuth($apiCall, $this->sFoxrateAPIUsername, $this->sFoxrateAPIPassword);

    }


    /**
     * Load Seller id, which is needed for multiple api calls
     *
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function loadSellerId_Foxrate()
    {
        $myConfig = $this->config;
        $apiCall = $this->apiCallBuilder("currentSellerId", "json");

        $resultObject = $this->makeRequestBasicAuth($apiCall, $this->sFoxrateAPIUsername, $this->sFoxrateAPIPassword);

        if (empty($resultObject->id)) {
            throw new Foxrate_Sdk_ApiBundle_Exception_Communicate(
                "Couldn't get current seller Id from Foxrate. Url: " . $apiCall,
                FOX_API_COMMUNICATE_NO_SELLER_ID
            );
        }
        $myConfig->saveShopConfVar('foxrateSellerId', $resultObject->id, 'string');
        $this->sFoxrateAPI2sellerId = $resultObject->id;
    }

    /**
     * Load Seller id from cache, which is needed for multiple api calls
     * @return mixed
     */
    protected function loadSellerId_Cache()
    {
        $sellerId = $this->config->getConfigParam('foxrateSellerId');

        if (empty($sellerId)) {
            $this->loadSellerId_Foxrate();
        } else {
            $this->sFoxrateAPI2sellerId = $sellerId;
        }
    }

    /**
     * Load Seller id from cache, which is needed for multiple api calls
     *
     * @return mixed
     */
    protected function loadShopId_Cache()
    {
        $myConfig = $this->config;
        $shopId = $myConfig->getConfigParam('foxrateShopId');

        if (is_null($shopId)) {
            $this->loadShopId_Foxrate();
        } else {
            $this->sFoxrateAPIShopId = $shopId;
        }

    }

    /**
     * Load shop and channel id from Foxrate's api. List of channels is returned and one is selected which matches with domain
     * the import is being ran on
     *
     * @return int
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function loadShopId_Foxrate()
    {

        $myConfig = $this->config;
        $shopIdOverride = $myConfig->getConfigParam('foxrateOverrideShopId');
        if (isset($shopIdOverride)) {
            $this->sFoxrateAPIShopId = $shopIdOverride;
            return 0;
        }

        $apiCall = $this->apiCallBuilder("currentSellerChannelsId", "json");
        $ShopIdAndUrl = $this->makeRequestBasicAuth($apiCall, $this->sFoxrateAPIUsername, $this->sFoxrateAPIPassword);
        if (!$ShopIdAndUrl[0]->id) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "Couldn't get shop Id from Foxrate. Url: " . $apiCall,
                FOX_LOAD_SHOP_ID_COULD_NOT_GET_SHOP_ID
            );
        }
        $ShopId = $this->findShopIdByUrlMatch($ShopIdAndUrl);
        if (!$ShopId) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "The url received from Foxrate's api does not match this domain. This url '" . $this->config->getShopUrl() . "'",
                FOX_LOAD_SHOP_ID_URL_DOES_NOT_MATCH
                );
        }

        $myConfig->saveShopConfVar('foxrateShopId', $ShopId, 'string');
        $this->sFoxrateAPIShopId = $ShopId;
    }

    /**
     * Find shop id from given object, that matches it's url and this domain
     */
    protected function findShopIdByUrlMatch($ShopIdAndUrl)
    {
        $domainRaw = $this->config->getHomeUrl();
        $domain = preg_replace("/(http:\/\/|https:\/\/|www.)/", "", $domainRaw);

        foreach ($ShopIdAndUrl as $singleBlock) {
            $cleanUrl = preg_replace("/(http:\/\/|https:\/\/|www.)/", "", $singleBlock->url);
            $matchResult = preg_match("/" . $cleanUrl . ".*|.*hotdigital.*/", $domain);
            if ($matchResult == 1 || $matchResult == true) {
                return $singleBlock->id;
            }
        }
        return false;
    }

    /**
     * Build api call by given scenario
     *
     * @param $method
     * @param string $format
     * @param bool $params
     * @return string
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    public function apiCallBuilder($method, $format = "json", $params = false)
    {
        $call = "";
        $extraParams = "";


        $callBase = $this->getFoxrateApiUrl() . "/" . $this->sFoxrateAPI2version;
        switch ($method) {
            case "currentSellerId":
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/id";
                break;
            case "productGeneral":
                if (!is_array($params)) {
                    throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                        'Params not set!',
                        FOX_API_CALL_BUILD_PARAMS_MISSING
                    );
                }
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->sFoxrateAPI2sellerId . "/" . $this->sFoxrateAPI2products . "/" . $params["productId"] . "/" . $this->sFoxrateAPI2ratings;
                $extraParams = $this->apiCallOptionalParamsBuilder();
                break;
            case "currentSellerChannelsId":
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->sFoxrateAPI2sellerId . "/" . $this->sFoxrateAPI2channels;
                break;
            case 'voteProductReview':
                if (!is_array($params)) {
                    throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                        'Params not set!',
                        FOX_API_CALL_BUILD_PARAMS_MISSING
                    );
                }
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->sFoxrateAPI2sellerId . "/" . $this->sFoxrateAPI2products;
                $call .= "/" . $this->sFoxrateAPI2reviews . "/" . $params["reviewId"] . "/" . $this->sFoxrateAPI2vote;
                break;
            case 'abuseReview':
                if (!is_array($params)) {
                    throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                        'Params not set!',
                        FOX_API_CALL_BUILD_PARAMS_MISSING
                    );
                }
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->sFoxrateAPI2sellerId . "/" . $this->sFoxrateAPI2products;
                $call .= "/" . $this->sFoxrateAPI2reviews . "/" . $params["reviewId"] . "/" . $this->sFoxrateAPI2abuse;
                break;
            case "productReviews":
                if (!is_array($params)) {
                    throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                        'Params not set!',
                        FOX_API_CALL_BUILD_PARAMS_MISSING
                    );
                }
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->sFoxrateAPI2sellerId . "/" . $this->sFoxrateAPI2products . "/" . $params["productId"];
                $call .= "/" . $this->sFoxrateAPI2reviews;
                $extraParams = $this->apiCallOptionalParamsBuilder();
                break;
            case "reviews":
                //http://api.foxrate.com/v1/sellers/5240/products/reviews.json
                $call = $callBase . "/" . $this->sFoxrateAPI2sellers . "/" . $this->getFoxrateSellerId() . "/" . $this->sFoxrateAPI2products;
                $call .= "/" . $this->sFoxrateAPI2reviews;
                break;

        }
        $call .= "." . $format . $extraParams;
        return $call;
    }


    /**
     * If optional/additional parameters are set, they are added to api call.
     * @return string
     */
    protected function apiCallOptionalParamsBuilder()
    {
        $urlParams = "filter[channel]=" . $this->getFoxrateShopId() . "&";
        $paramName = "";
        foreach ($this->sFoxrateSettings as $settingKey => $settingValue) {
            if (($settingKey != "") && ($settingValue != "")) {
                switch ($settingKey) {
                    case 'foxratePR_SortBy':
                        $paramName = "sort_by";
                        $urlChanges = true;
                        break;
                    case 'foxratePR_SortOrder':
                        $paramName = "sort_order";
                        $urlChanges = true;
                        break;
                    case 'foxratePR_Page':
                        $paramName = "page";
                        $urlChanges = true;
                        break;
                    case 'foxratePR_RevsPerPage':
                        $paramName = "limit";
                        $urlChanges = true;
                        break;
                    default:
                        $urlChanges = false;
                        break;
                }
                if ($urlChanges) {
                    $urlParams .= $paramName . "=" . $settingValue . "&";
                }
            }
        }

        if ($urlParams != "") {
            $urlParams = "?" . $urlParams;
        }
        return $urlParams;
    }


    /**
     * Throws Foxrate_Sdk_ApiBundle_Exception_ModuleException if user does not exist
     * @throws Foxrate_Sdk_ApiBundle_Exception_ModuleException
     */
    protected function checkUserExist()
    {
        if (!$this->sFoxrateAPIUsername) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "Foxrate Api Username is not set",
                FOX_CHECK_USER_IS_NOT_SET
            );
        }
        if (!$this->sFoxrateAPIPassword) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "Foxrate Api Password is not set",
                FOX_CHECK_PASS_IS_NOT_SET
            );
        }
        if (!$this->foxrateConnector->wrapIsUserExist($this->sFoxrateAPIUsername, $this->sFoxrateAPIPassword)) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                "User is not found in Foxrate: Username - '" . $this->sFoxrateAPIUsername . "' password - '" . $this->sFoxrateAPIPassword . "'",
                FOX_CHECK_USER_NOT_FOUND
            );
        }
    }

    /**
     * Make a request via CURL with given headers and params to specific URL, using Masic Auth
     *
     * @param $sUrl
     * @param $username
     * @param $password
     * @param array $aHeaders
     * @param null $aParams
     * @return bool|mixed
     *
     * @deprecated You should use  Foxrate_Sdk_ApiBundle_Caller_FoxrateApiCaller makeCall() method instead
     */
    public function makeRequestBasicAuth($sUrl, $username, $password, $aHeaders = array(), $aParams = null)
    {
        $ch = curl_init();

        $opts[CURLOPT_CONNECTTIMEOUT] = 10;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_TIMEOUT] = 60;
        $opts[CURLOPT_HTTPHEADER] = $aHeaders;
        $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $opts[CURLOPT_USERPWD] = trim($username) . ":" . trim($password);
        $opts[CURLOPT_URL] = $sUrl;
        if (!is_null($aParams)) {
            $opts[CURLOPT_POSTFIELDS] = $aParams;
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || $code != 200) {

            $en = curl_errno($ch);
            $e = curl_error($ch);
            curl_close($ch);
            $this->config->writeToLog("Warning: cUrl Error: " . $en . " - " . $e);
            $this->config->writeToLog("Warning: cUrl Error url: " . $sUrl);
        }

        if (gettype($ch) == 'resource') {
            curl_close($ch);
        }

        return json_decode($result);
    }

    public function makeMageRequest($url, $username, $password, $headers = null, $params = null)
    {
        $client = new Varien_Http_Client();

        $client->setAuth($username, $password, Zend_Http_Client::AUTH_BASIC);

        $client->setUri($url)
            ->setMethod('GET')
            ->setConfig(
                array(
                    'maxredirects' => 0,
                    'timeout' => 60,
                )
            );

        if (isset($headers)) {
            $client->setHeaders($headers);
        }

        if (isset($params)) {
            $client->setRawData($params);
        }

        $result = $client->request();

        try {
            return $this->handleMageRequest($result);
        } catch (Zend_Http_Client_Exception $e) {
            $error_message = 'There was error with API. ' . $e->getMessage();
            $this->config->writeToLog($error_message);
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException ($error_message);
        }

    }

    protected function handleMageRequest(Zend_Http_Response $result)
    {
        if ($result->isError()) {
            $bodyObject = json_decode($result->getRawBody());
            $error_message = "Api returned error with message: " . $bodyObject->status_text;
            $this->config->writeToLog($error_message);
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException($error_message);
        }

        return json_decode($result->getRawBody());
    }

    /**
     * Checks if the cache on demand for single file has already expired
     * @param $prodId
     * @param string $format
     *
     * @return bool
     */
    protected function hasCacheExpired_CacheDemand($prodId, $format = 'json')
    {
        $path = $this->config->getCachedReviewsPath() . "/" . $prodId . ".gener." . $format;
        if (!file_exists($path)) {
            return true;
        }
        $changedTime = filemtime($path);
        $expireDate = strtotime("- {$this->sReviewsExpirePeriodCacheDemand} hours");
        if ($changedTime <= $expireDate) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if product review cache has expired
     * @return bool
     */
    protected function hasCacheExpired()
    {
        return true;
    }

    /**
     * @return array
     */
    public function disabledSummaryError()
    {
        return array("error" => 'Summary is turned off');
    }

    /**
     * @param $isAllowed
     * @return bool
     */
    public function isDisabled($isAllowed)
    {
        return $isAllowed == 'off' || is_null($isAllowed);
    }

    /**
     * Foxrate product review logger
     * @param $eventMessage
     * @return mixed
     */
    protected function eventLogger($eventMessage)
    {
        $this->cleanLog();
        $time = date("Y.m.d H:i:s");
        $logMessage = $time . " " . $eventMessage . "\n";
        oxUtils::getInstance()->writeToLog($logMessage, $this->sFoxrateLoggerFileName);
        return $eventMessage;
    }

    /**
     * Log cleaning
     */
    protected function cleanLog()
    {
        $logsDir = $this->config->getLogsDir();
        $foxLog = $logsDir . $this->sFoxrateLoggerFileName;
        if (file_exists($foxLog)) {
            $size = filesize($foxLog);
            $mSize = ($size / 1024) / 1024;
            if ($mSize >= 20) {
                unlink($foxLog);
            }
        }
    }

    /**
     * Downloads the log
     */
    public function downloadLog()
    {
        $logsDir = $this->config->getLogsDir();
        $foxLog = $logsDir . $this->sFoxrateLoggerFileName;
        if (file_exists($foxLog)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $this->sFoxrateLoggerFileName);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($foxLog));
            ob_clean();
            flush();
            readfile($foxLog);
            flush();
        }
    }


    /**
     * Converts object to array
     * @param $data
     * @return array
     */
    public function convertObjectToArray($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->convertObjectToArray($value);
            }
            return $result;
        }
        return $data;
    }

    /**
     * Loads all the pages of a review from cache (needed when all reviews have to be displayed)
     * @param $prodId
     * @return array
     */
    public function loadProductsAllRevs_Cache($prodId)
    {
        $pages = 0;
        $reviewCollection = array();

        do {
            $pages++;
            $reviewsPage = $this->dataManager->loadCachedProductReviews($prodId, $pages);
            if ($reviewsPage) {
                $reviewCollection = array_merge(
                    $reviewCollection,
                    $reviewsPage->reviews
                );
            }
        } while ($pages < $reviewsPage->pages_count);

        $reviewsPage->reviews = $reviewCollection;

        return $reviewsPage;
    }

    /**
     * Calculates date
     * @param $date
     * @return mixed
     */
    public function calcReviewDate($date)
    {
        $matches = array();
        $result = preg_match($this->regExpDateFromApi, $date, $matches);
        if ($result) {
            return $matches[1];
        }
    }

    /**
     * Loads page navigation, gets neighbouring values calculated from current page number, filtering negative ones,
     * and those which are bigger than the page limit
     * @param $totalPages
     * @param $currentPage
     * @return array
     */
    public function getPageNav($totalPages, $currentPage)
    {
        $pageCounts = array(-2, -1, 0, 1, 2);
        $pageNav = array();
        foreach ($pageCounts as $pageCount) {
            $result = $currentPage + $pageCount;
            if (($result > 0) && ($result <= $totalPages)) {
                if ($result == $currentPage) {
                    $pageNav = array_merge($pageNav, array('current' => $result));
                } else {
                    $pageNav = array_merge($pageNav, array('other' . $result => $result));
                }
            }
        }
        return $pageNav;
    }

    /**
     * Generates sorting criteria for user reviews
     */
    public function getSortingCriteria()
    {
        $sortingCriteria = array(
            '' => '',
            'date_asc' => 'Date ↑',
            'date_desc' => 'Date ↓',
            'rate_asc' => 'Rating ↑',
            'rate_desc' => 'Rating ↓'
        );
        return $sortingCriteria;
    }

    /**
     * Send voting to foxrate api
     */
    public function voteReview($revId, $useful)
    {
        if ($useful == 'true') {
            $useful = true;
        } else {
            $useful = false;
        }
        $this->loadSellerId_Cache();
        $params = array("reviewId" => $revId);
        $url = $this->apiCallBuilder('voteProductReview', 'json', $params);
        $postParams = json_encode(array('useful' => $useful));
        $resultRaw = $this->makeRequestBasicAuth(
            $url,
            $this->config->getShopConfVar('foxrateUsername'),
            $this->config->getShopConfVar('foxrateUsername'),
            array("Content-type: application/json"),
            $postParams
        );
        return $resultRaw;
    }


    /**
     * Report abuse review to foxrate api
     */
    public function abuseReview($revId, $abuse)
    {
        if ($abuse == 'true') {
            $abuse = true;
        } else {
            $abuse = false;
        }
        $this->loadSellerId_Cache();
        $params = array("reviewId" => $revId);
        $url = $this->apiCallBuilder('abuseReview', 'json', $params);
        $postParams = json_encode(array('abuse' => $abuse));
        $resultRaw = $this->makeRequestBasicAuth(
            $url,
            $this->config->getShopConfVar('foxrateUsername'),
            $this->config->getShopConfVar('foxrateUsername'),
            array("Content-type: application/json"),
            $postParams
        );

        if ($this->isMakeRequestError($resultRaw)) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                'There was error during this action'
                );
        }

        return $resultRaw;
    }

    protected function isMakeRequestError($result)
    {
        if ($result->status == 'error') {
            return true;
        }

        return false;
    }

    /**
     * Gets link to write reviews
     */
    public function getWriteReviewLink($prodId)
    {

        $isAllowed = $this->config->getConfigParam('foxratePR_WriteReview');
        if ($isAllowed == 'off' || is_null($isAllowed)) {
            return null;
        }

        $lang = $this->config->getLanguageAbbr();
        $this->loadSellerId_Cache();
        $this->loadShopId_Cache();

        //http://foxrate.vm/product_rate/en/2820/shop_58/
        //http://foxrate.de/product_rate/de/15170/shop_1/315a1773d274183955625d030225fcc9


        $link = $this->getFoxrateUrl()
            . $this->sFoxrateProdProfLink
            . "/" . $lang
            . "/" . $this->sFoxrateAPI2sellerId
            . "/" . $this->getFoxrateShopId()
            . "/" . $prodId;

        return $link;
    }

    public function getTotalReviews($generalReview)
    {
        if (!isset($generalReview)) {
            throw new Foxrate_Sdk_ApiBundle_Exception_ModuleException(
                'General product review info not given!',
                FOX_GET_TOTAL_REVIEWS_INFO_NOT_GIVEN
            );
        }
        return $generalReview['count'];
    }

    public function getFoxrateApiUrl()
    {
        return $this->environment->getFoxrateApiUrl();
    }

    public function getFoxrateUrl()
    {
        return $this->environment->getFoxrateUrl();
    }

    /**
     * Controller return true or false if richsnippet options is enabled or disabled
     * @return bool
     */
    public function richSnippetIsActive()
    {

        $isActive = $this->config->getConfigParam('foxratePR_OrderRichSnippet');
        $hasProblems = $this->config->isRichSnippetProblem();

        if ($isActive == 'off' || is_null($isActive) || $hasProblems) {
            return false;
        } else {
            return true;
        }
    }

    public function getFoxrateShopId()
    {
        if (null === $this->sFoxrateAPIShopId) {
            throw new InvalidArgumentException('Foxrate shop id is not set.');
        }
        return $this->sFoxrateAPIShopId;
    }

    public function showFoxrateCategoryRatings()
    {
        return $this->config->getConfigParam('foxrateShowRatingsInCategory') == 'on';
    }

    public function getCategoryErrorMap()
    {

        $isAllowedCatDisp = $this->config->getConfigParam('foxratePR_CatalogDisplay');
        $isAllowedTooltip = $this->config->getConfigParam('foxratePR_CatalogTooltip');
        $errors = array('Catalog_display' => 'true', 'Catalog_tooltip' => 'true');
        if ($isAllowedCatDisp == 'off' || is_null($isAllowedCatDisp)) {
            $errors['Catalog_display'] = 'false';
        }

        if ($isAllowedTooltip == 'off' || is_null($isAllowedTooltip)) {
            $errors['Catalog_tooltip'] = 'false';
        }

        return $errors;
    }

    public function isSummaryDisbaled()
    {
        $isAllowed = $this->config->getConfigParam('foxratePR_Summary');

        if ($this->isDisabled($isAllowed)) {
            return $this->disabledSummaryError();
        }

        return false;
    }

    protected function sortReviewCounts($info)
    {
        if (is_array($info['counts'])) {
            krsort($info['counts']);
        }
        return $info;

    }

    /**
     * Some shops displays reviews on tab or other page, we must generate a specific link for them.
     * @return mixed
     */
    public function getReviewsUrl()
    {
        return $this->config->getReviewsUrl();
    }

    public function getFoxrateSellerId()
    {
        return $this->sFoxrateAPI2sellerId;
    }

    public function setFoxrateSellerId($sellerId)
    {
        $this->sFoxrateAPI2sellerId = $sellerId;
    }
}

