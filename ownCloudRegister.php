<?php
/*
 * This is a PHP file that handle the register action of the ownCloud Registration
 *    - Documentation and latest version
 *          https://github.com/Anthony-95/
 *
 * AUTHORS:
 *   Antonio Risueño Sánchez
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/*
 * Options used for the cURL transfer
 * -> CURLOPT_URL:	The URL to fetch.
 * -> CURLOPT_HEADER:   TRUE to include the header in the output.
 * -> CURLOPT_TIMEOUT:  The maximum number of seconds to allow cURL functions to execute.
 * -> CURLOPT_RETURNTRANSFER:   TRUE to return the transfer as a string of the return value of curl_exec(),
 * -> CURLOPT_POST: TRUE to do a regular HTTP POST.
 * -> CURLOPT_POSTFIELDS:   The full data to post in a HTTP "POST" operation.
 *
 * More Info: http://php.net/manual/en/function.curl-setopt.php
 * */

/**
 * Finalise the process
 * @param $error - the type of error occurred
 *
 * Why JSON_PRETTY_PRINT ? Why not ? ;)
 */
function exit_process ($error = "SUCCESS") {
    // Save the status
    $GLOBALS['jsondata']['status'] = $error;
    // Send the info back
    echo json_encode($GLOBALS['jsondata'], JSON_PRETTY_PRINT);
    // End exec
    die();
}

/*
 * Types of Errors
 * -> Users Errors: These errors are induced by the input of the user
 *      Identified by the status "ERROR"
 *      The UI will show the array of errors introduced
 * -> Stage Errors: These errors are returned by the API
 *      Identified by ERR-S[id]-[response]
 *          Where [id] is the stage of the process and [response] the status code returned by ownCloud
 *          (With this we can have a little bit of traceability)
 *      The UI will display the error as is
 * -> System Errors: Errors produced by the database or other components
 *      There not specially identified and will be presented in the same way that the others
 */

// Header to return JSON data
header("Content-Type: application/json; charset=UTF-8");

// Initial connection to database
require_once('lib/DBConnect.php');
// Functions to interact with the ownCloud Server
require_once('lib/ownCloudAPI.php');

// The return array
$jsondata = array();
$jsondata['status'] = "READY"; // This will be replaced with the real status at the end

/*
 * MAIN FUNCTION TO ACCOMPLISH THE REGISTRATION
 * METHOD: POST
 * POST FIELDS:
 * -> user - name of the new user
 * -> name - display name of the new user
 * -> pass - pass of the new user
 * -> email - email of the new user
 * -> code - invitation code to perform the operation
 * -> reCAPTCHA - response to the challenge
 *
 * NOTE: If any of these fields is missing or empty the whole process cancels
*/
if ($_POST) {
    // Export global variable to local environment
    global $jsondata;

    // Default quota for new users
    $quota = "2GB"; // This var will be replaced with the actual quota codified in the invitation code

    // POST data check
    if (!isset($_POST['name']) || empty($_POST['name']) || !isset($_POST['user'])  || empty($_POST['user'])  ||
        !isset($_POST['pass']) || empty($_POST['pass']) || !isset($_POST['email']) || empty($_POST['email']) ||
        !isset($_POST['code']) || empty($_POST['code']) || !isset($_POST['g-recaptcha-response'])) {
        exit_process("ARGS_CHECK_FAIL");
    }

    /** reCAPTCHA check **/
    if (empty($_POST['g-recaptcha-response'])) {
        $jsondata["errors"][] = "The reCAPTCHA verification can not be incomplete.";
        exit_process("ERROR");
    } else {
        // reCAPTCHA private key
        $private_key = "YOUR reCAPTCHA PRIVATE KEY ";

        // CURL process to call Google verification
        $curlRequest = curl_init();

        // Payload for this operation (reCAPTCHA Response)
        $data = array("secret" => $private_key, "response" => $_POST['g-recaptcha-response'], $_SERVER["REMOTE_ADDR"]);

        // CURL options for this task
        curl_setopt($curlRequest, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($curlRequest, CURLOPT_HEADER, 0);
        curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlRequest, CURLOPT_POSTFIELDS, http_build_query($data));

        // Exec CURL request to fetch the API
        $return = curl_exec($curlRequest);

        // Finish CURL process
        curl_close($curlRequest);

        // Parse the return of CURL from JSON to an Associative Array
        $response = json_decode($return, true);

        if (!$response['success']) {
            $jsondata["errors"][] = "The verification against automated programs hasn't been passed.";
            exit_process("ERROR");
        }
    }

    // ------------------------------------------------ //
    //  Google reCAPTCHA approves the response as good  //
    // ------------------------------------------------ //

    /** Invitation code check **/
    if (preg_match("/[A-z0-9]{50}/", $_POST['code'])) {
        $result = $database->prepare("SELECT quota, time FROM drive WHERE code = :code");
        $row = $result->execute([':code' => $_POST['code']]);

        // Obtain the details linked to an specific code
        while ($row = $result->fetchAll(PDO::FETCH_ASSOC)) {
            if ($row['0']['quota'] == NULL || $row['0']['time'] != NULL) {
                $jsondata["errors"][] = "The invitation code does not exist or is already in use";
                exit_process("ERROR");
            }
            // Save the quota value for later on
            $quota = $row['0']['quota'];
        }
        // Free vars
        unset($result);
        unset($row);

    } else {
        $jsondata["errors"][] = "The invitation code is not in the correct format.";
        exit_process("ERROR");
    }

    // -------------------------------------------------- //
    //  The invitation code has good format and is valid  //
    // -------------------------------------------------- //

    /** Name format check **/
    if (!preg_match("/[A-záéíóúñÁÉÍÓÚÑ ]{5,25}/", $_POST['user'])) {
        $jsondata["errors"][] = "The name can contain letters, accents and spaces.";
        $jsondata["errors"][] = "Special characters and numbers will be removed from the name.";
        $jsondata["errors"][] = "The name must be between 5 and 25 characters.";
        exit_process("ERROR");
    }

    // -------------------------- //
    //  The name has good format  //
    // -------------------------- //

    /** Check is the user name already exits in the server */
    if(api_user_get($_POST['user'])) {
        $jsondata["errors"][] = "The name already exists in the system, choose another.";
        exit_process("ERROR");
    }

    // -------------------------------------- //
    //  The name doesn't exits in the server  //
    // -------------------------------------- //

    /** Nick format check **/
    if (!preg_match("/[0-9A-záéíóúñÁÉÍÓÚÑ _-]{5,30}/", $_POST['name'])) {
        $jsondata["errors"][] = "The user can contain letters, titles, spaces and hyphens (including _).";
        $jsondata["errors"][] = "Special characters will be deleted from the user.";
        $jsondata["errors"][] = "The user must be between 5 and 30 characters.";
        exit_process("ERROR");
    }

    // ------------------------------- //
    //  The nick name has good format  //
    // ------------------------------- //

    /** Pass format check **/
    if(!preg_match("/(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#_!@$%&*-+]).{7,}/", $_POST['pass'])) {
        $jsondata["errors"][] = "The password must be at least 7 characters long.";
        $jsondata["errors"][] = "The password must contain at least one uppercase and one lower case.";
        $jsondata["errors"][] = "The password must contain at least one number.";
        $jsondata["errors"][] = "The password must contain at least one special character like !@#$%&*-_+";
        exit_process("ERROR");
    }

    // -------------------------------------------------------------------- //
    //  The password has good format and complies with the security policy  //
    // -------------------------------------------------------------------- //

    /** Email format check **/
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !checkdnsrr(explode('@', $_POST['email'])[1], 'MX')) {
        $jsondata["errors"][] = "The email address is not formatted correctly or does not exist.";
        exit_process("ERROR");
    }

    // --------------------------------------------- //
    //  The email address format is valid and exits  //
    // --------------------------------------------- //

    /*
     * With all checked we're going to start the registration procedure into the ownCloud server
     *
     * Note: The success code for the ownCloud API is 100
     */

    /** Server status check **/
    if (!api_user_get('YOUR ADMIN USER')) {
        exit_process("SERVER_UNREACHABLE");
    }

    // ---------------------------------------- //
    //  The server and the API are accessible   //
    // ---------------------------------------- //

    /** Create the new user account **/
    $response = api_user_add($_POST['user'], $_POST['pass']);
    if ($response != 100) exit_process(sprintf("ERR-S%d-%d", 1, $response));

    // ------------------------------- //
    //  The new user has been created  //
    // ------------------------------- //

    /** Update the user quota **/
    $response = api_quota_update($_POST['user'], $quota, $_POST['code']);
    if ($response != 100) exit_process(sprintf("ERR-S%d-%d", 2, $response));

    // --------------------------------- //
    //  The user quota has been updated  //
    // --------------------------------- //

    /** Update the user display name **/
    $response = api_name_update($_POST['user'], $_POST['name']);
    if ($response != 100) exit_process(sprintf("ERR-S%d-%d", 3, $response));

    // --------------------------------------- //
    //  The user display new has been updated  //
    // --------------------------------------- //

    /** Update the user email **/
    $response = api_email_update($_POST['user'], $_POST['email']);
    if ($response != 100) exit_process(sprintf("ERR-S%d-%d", 4, $response));

    // --------------------------------- //
    //  The user email has been updated  //
    // --------------------------------- //

    /*
     * OPTIONAL: check if the group exits
     */

    /** Add user to a group **/
    $response = api_user_add_group($_POST['user'], "Users");
    if ($response != 100) exit_process(sprintf("ERR-S%d-%d", 5, $response));

    // ---------------------------------------- //
    //  The new user has been added to a group  //
    // ---------------------------------------- //

    /**  Check if the user is where is suppose to be*/
    if (!api_user_get($_POST['user'])) {
        // Only in case of failure of the server (It should never happen)
        exit_process("INVALID_USER");
    }

    // -------------------------------------------- //
    //  Final check of the new user (Just in case)  //
    // -------------------------------------------- //

    /** Save the information into the database and validate the process **/
    $result = $database->prepare("UPDATE drive SET time = NOW(), name = :name, user = :user, pass = :pass, email = :email, ip = :ip WHERE code = :code");
    $result->bindParam(":name", $_POST['name']);
    $result->bindParam(":user", $_POST['user']);
    $result->bindParam(":pass", hash('sha256', $_POST['pass']));
    $result->bindParam(":email", $_POST['email']);
    $result->bindParam(":ip", $_SERVER["REMOTE_ADDR"]);
    $result->bindParam(":code", $_POST['code']);

    // Query exec
    if ($result->execute())
        // We are finished, exiting...
        exit_process();
    else
        // Only in case of failure of the database (It should never happen)
        exit_process("INVALID_PROCESS");
} else {
    die("INCORRECT_METHOD"); // In case of using other method
}