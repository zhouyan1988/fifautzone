<?php
namespace Izberg;

class Helper
{
  /**
	* Camelize a string
  * @param string $value Text to camelize
	*/
  public function camelize($value)
  {
    return strtr(ucwords(strtr($value, array('_' => ' ', '.' => '_ ', '\\' => '_ '))), array(' ' => ''));
  }

  /**
	* Uncamelize a string
  * @param string $value Text to uncamelize
  * @param string $splitter Char to use to split
	*/
  public function uncamelize($value,$splitter="_") {
    $value=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $value));
    return strtolower($value);
  }

  public function readFromUrl($url, $output_file = null)
  {
    $maxTries = 3;
    $upload = false;
    for ($try=1; $try<=$maxTries; $try++) {
        $upload = $this->get_url_contents($url, $output_file);
        if ($upload) {
            break;
        }
    }
    return $upload;
  }

  private function get_url_contents($url, $output_file = null){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, $url);

      if ($output_file) {
        var_dump("use file");
        $fp = fopen ($output_file, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
      } else {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      }

      // curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1");
      // curl_setopt($ch, CURLOPT_PROXYPORT, 8888);

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
}
