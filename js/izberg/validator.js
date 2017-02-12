Validation.add('validate-json', 'Please enter a valid json format.', function(v) {
    $result = false;
    try {
      JSON.parse(v);
      $result = true;
    } catch (e) {
      $result = false;
    }
    return $result;
})
