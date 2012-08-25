<?php
/***************************************************************************//**
 * @brief Generate classmaps and connect to service
 *
 */
class generateSoapClient {

    /***********************************************************************//**
     * @brief Connect to the web service
     *
     * Connect to the web service for the given uri and options
     *
     * @param $wsdlUri The uri to the WSDL document for the web service
     * @param $options [optional] The option parameters for the
     * <a href="http://www.php.net/manual/en/soapclient.soapclient.php">SoapClient</a> command. Used here with the classmap array.
     *
     * @return Client handle the $wsdlUri webservice
     */
    public function createSoapClient($wsdlUri,$options="none") {

        // connect to web service to get types
        $client = new SoapClient($wsdlUri);

        $types = array();   // init $types as an array

        // Get the data type provided by this webservice
        $a = $client->__getTypes();

        // Loop through each data type and create a dynamic class for each type
        foreach ($a as $soapType) {

            // Remove the \n from the $soapType
            $soapType = preg_replace('/\n/',' ', $soapType);

            // Split the $soapType into it's parts
            preg_match("/([a-z0-9_]+)\s+([a-z0-9_]+)\s+{\s+(.*)}/si", $soapType, $matches);

            //print_r($matches);

            // Some calls don't have a return skip function creation
            if (count($matches) != 4 || !$matches[3]) {
                continue;
            }

            // Remove multiple spaces
            $matches[3] = preg_replace('/;\s+/',';',$matches[3]);

            // Split the parameters on ';'
            $props = explode(';',$matches[3]);

            $type = $matches[1];
            $name = $matches[2];

            // Skip if the class name starts with Test.
            // There are classes in the dmObjectAccess WS that have bad data in them.
            if (preg_match('/^Test/', $name)) {
                continue;
            }

            // Switch on the type of object
            switch($type) {

                // if the data type is struct, we create a class with this name
                case 'struct':
                    // set class name to the returned name of the object
                    $className = $name;

                    // store the data type information in an array for later use in the classmap
                    $types[$name] = $className;
                    $params = '';
                    $propArray = '';

                    // Process each of the properties
                    foreach ($props as $prop) {
                        // Skip if no prop - blank line in the structure
                        if (!$prop){
                            continue;
                        }

                        // Split the property
                        list($propType, $propName) = explode(' ',$prop);

                        // Switch on the property type
                        switch($propType) {
                            // Create an object for this data type
                            case 'string':
                                $params[] = '$'.$propName;
                                $propArray[] = '$this->'.$propName.' = $'.$propName;
                                break;
                            // Create an object for this data type
                            case 'boolean':
                                $params[] = '$'.$propName;
                                $propArray[] = '$this->'.$propName.' = $'.$propName;
                                break;
                            // Create an object for this data type and create a new object ...
                            default:
                                $params[] = '$'.$propName;
                                $propArray[] = '$this->'.$propName.' = new '.$propType.'($'.$propName.')';
                                break;
                        }
                    }

                    // Build class string
                    $func = $className.'
                    {
                        function __construct('.implode('=NULL,',$params).'=NULL)
                        {'
                            .implode(';',$propArray).';
                        }
                    }';

                    // Check the class does not exsits before creating it
                    if (!class_exists($className)) {
                        //print_r($func);
                        eval("class $func");
                    }

                    break;
                // Do nothing if type is string;
                case 'string':
                    break;
            }
        }

        // Return a client handle for the web service with the generated classmap
        return new SoapClient($wsdlUri, array ('classmap' => $types));
    }

    /***************************************************************************//**
     * Block instantiation
     */
    public function __construct() {

    }
}
?>