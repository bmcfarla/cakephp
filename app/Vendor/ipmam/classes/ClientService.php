<?php
/***************************************************************************//**
 * @brief Create and connect to Web Services
 *
 */
class clientService {
    private $client;    //!< Stores the returned client handle
    private $uri;       //!< Stores the uri for the naming WS

    /***********************************************************************//**
     * @brief Connect to the Naming service
     *
     * Instantiate an object with a connection to the Naming WS that can resovle
     * web service names and connect to the requested web service.
     */
    public function __construct() {
        // Store the uri for the Naming Service
        $this->uri = "http://ma-admin01:12000/ConfigurationServiceWS/Naming.asmx?WSDL";
        //print "HELLO from Client Service";
        //exit;
        // Connect the Naming service and store the client handle
        $this->client = generateSoapClient::createSoapClient($this->uri);
    }

    /***********************************************************************//**
     * @brief Connect to the web service
     *
     * @param $name The name of the web service to resolve. ex. 'UserManagementWS\\UM'
     */
    public function createClient ($name) {
        // Connect to the the web service and return the client;
        return generateSoapClient::createSoapClient($this->resolveUri($name)."?WSDL");
    }

    /***********************************************************************//**
     * @brief Resovle the uri for the given web service name
     *
     * @param $name The name of the web service to resolve. ex. 'UserManagementWS\\UM'
     */
    private function resolveUri ($name) {
        // Get and return the uri for the named web service
        return $this->client->Resolve(new Resolve($name))->ResolveResult;
    }
}
?>