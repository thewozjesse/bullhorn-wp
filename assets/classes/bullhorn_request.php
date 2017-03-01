<?php 

if ( !class_exists( 'BullhornRequest' ) ) {

    class BullhornRequest {
        
        /**
         * Use cURL to send a POST request
         * @param  string        $url    the URL to POST to
         * @param  string/array  $data   the data to send
         * @param  boolean       $header include headers?
         * @return string                data returned from the cURL request
         */
        public function post($url, $data, $header = false)
        {
            // format data for curl
            if (is_array($data)) {
                $formatted_data = implode('&', array_map(array($this, '_concatenateKeyValuePairs'), array_keys($data), array_values($data)));
            } else {
                $formatted_data = '';
            }
            
            $options = array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $formatted_data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120,
            );
            
            if ($header) {
                $options[CURLOPT_HEADER] = true;
            }

            $ch  = curl_init( $url );
            curl_setopt_array( $ch, $options );
            $content = curl_exec( $ch );

            curl_close( $ch );
            
            return $content;
        }
        
        public function get($url)
        {
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120,
            );

            $ch  = curl_init( $url );
            curl_setopt_array( $ch, $options );
            $content = curl_exec( $ch );

            curl_close( $ch );
            
            return $content;
        }
        
        /**
         * Returns an array key and value as the string 'key=value'
         * @param  string $key   array key
         * @param  string $value array value
         * @return string        concatenated key value pair as 'key=value'
         */
        private function _concatenateKeyValuePairs($key, $value)
        {
            return $key.'='.$value;
        }
        
    }
}
?>
