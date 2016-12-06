<?php
/*
 * This is a PHP library that handles calling ownCloud.
 *    - Documentation and latest version
 *          https://github.com/Anthony-95/
 *    - ownCloud Latest Server Administration Manual
 *          https://doc.owncloud.org/server/latest/admin_manual/contents.html
 *    - ownCloud User Provisioning API
			https://doc.owncloud.org/server/9.1/admin_manual/configuration_user/user_provisioning_api.html
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
 * -> CURLOPT_USERPWD:  A username and password formatted as "[username]:[password]" to use for the connection.
 * -> CURLOPT_TIMEOUT:  The maximum number of seconds to allow cURL functions to execute.
 * -> CURLOPT_RETURNTRANSFER:   TRUE to return the transfer as a string of the return value of curl_exec(),
 * -> CURLOPT_POST: TRUE to do a regular HTTP POST.
 * -> CURLOPT_POSTFIELDS:   The full data to post in a HTTP "POST" operation.
 * -> CURLOPT_CUSTOMREQUEST:    A custom request method to use instead of "GET" or "HEAD" when doing a HTTP request.
 * -> CURLOPT_SSL_VERIFYPEER:   FALSE to stop cURL from verifying the peer's certificate.
 *
 * More Info: http://php.net/manual/en/function.curl-setopt.php
 */

/*
 * Note about the parsed array of the response
 *  The ownCloud API outputs a XML response with everything surrounding by the tag "ocs"
 *      but when you access the array with -> the array is already inside that tag, so you can
 *      access directly to the tags "meta" and "data".
 */

/*
 * Details to access the ownCloud server URL and user
 */
$API_url = "https://your.ownCloud.server/ocs/v1.php/cloud";
$API_user = "YOUR ADMIN USER";
$API_pass = "YOUR ADMIN USER PASSWORD";
/*
 * Warning: The user must have the privileges necessary to perform all tasks described.
 */

/**
 * Obtain an user from the server
 * @param $user_to_test - name of the user to check
 * @return boolean - if the request has success or not
 */
function api_user_get($user_to_test) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users?search=%s", $GLOBALS['API_url'], $user_to_test);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);

    // Return the result of the operation and exit
    if ($response->meta->statuscode == 100 && $response->data->users->element == $user_to_test)
        return TRUE;
    else
        return FALSE;
}

/**
 * Add an user to the server with password
 * @param $user_to_add - name of the user to add
 * @param $user_pass - the new password for the user
 * @return integer - the status code returned from ownCloud
 */
function api_user_add($user_to_add, $user_pass) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users", $GLOBALS['API_url']);

    // Payload for this operation (User + Pass)
    $data = array("userid" => $user_to_add, "password" => $user_pass);
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_POST, 1);
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    return $response->meta->statuscode;
}

/**
 * Deletes an user from the server
 * @param $user_to_remove - name of the user to delete
 * @return boolean - if the request has success or not
 */
function api_user_del($user_to_remove) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s", $GLOBALS['API_url'], $user_to_remove);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    if ($response->meta->statuscode == 100 && $response->meta->status == "ok") // Double check just in case...
        return TRUE;
    else
        return FALSE;
}

/**
 * Update the quota of an user in the server
 * @param $user_to_update - name of the user to modify
 * @param $user_quota - new quota for the user
 * @return integer - the status code returned from ownCloud
 */
function api_quota_update($user_to_update, $user_quota) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s", $GLOBALS['API_url'], $user_to_update);
    
    // Payload for this operation (Quota)
    $data = array("key" => "quota", "value"  => $user_quota);
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    return $response->meta->statuscode;
}

/**
 * Update the display name of an user in the server
 * @param $user_to_update - name of the user to modify
 * @param $user_name - new display name for the user
 * @return integer - the status code returned from ownCloud
 */
function api_name_update($user_to_update, $user_name) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s", $GLOBALS['API_url'], $user_to_update);
    
    // Payload for this operation (Display Name)
    $data = array("key" => "display", "value"  => $user_name);
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    return $response->meta->statuscode;
}

/**
 * Update the email of an user in the server
 * @param $user_to_update - name of the user to modify
 * @param $user_email - new email for the user
 * @return integer - the status code returned from ownCloud
 */
function api_email_update($user_to_update, $user_email) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s", $GLOBALS['API_url'], $user_to_update);

    // Payload for this operation (Email)
    $data = array("key" => "email", "value"  => $user_email);
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    return $response->meta->statuscode;
}

/**
 * Obtain a group from the server
 * @param $group_to_test - name of the group to check
 * @return boolean - if the request has success or not
 */
function api_group_get($group_to_test) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/groups/%s", $GLOBALS['API_url'], $group_to_test);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);

    // Return the result of the operation and exit
    if ($response->meta->statuscode == 100 && $response->data->users->element == $group_to_test)
        return TRUE;
    else
        return FALSE;
}

/**
 * Add an user to an existing group in the server
 * @param $user_to_update - name of the user to modify
 * @param $group_name - the new group of the user
 * @return integer - the status code returned from ownCloud
 */
function api_user_add_group($user_to_update, $group_name = "Users") {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s/groups", $GLOBALS['API_url'], $user_to_update);

    // Payload for this operation (Group)
    $data = array("groupid"  => $group_name); // By default the group name is "Users"
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_POST, 1);
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    return $response->meta->statuscode;
}

/**
 * Remove an user from an existing group in the server
 * @param $user_to_update - name of the user to modify
 * @param $group_name - the old group of the user
 * @return boolean - if the request has success or not
 */
function api_user_del_group($user_to_update, $group_name) {
    // CURL process to call the API
    $curlRequest = curl_init();

    // Specific URL to perform this request
    $customURL = sprintf("%s/users/%s/groups", $GLOBALS['API_url'], $user_to_update);

    // Payload for this operation (Group)
    $data = array("groupid"  => $group_name);
    $data_string = http_build_query($data);

    // CURL options for this task
    curl_setopt($curlRequest, CURLOPT_URL, $customURL);
    curl_setopt($curlRequest, CURLOPT_HEADER, 0);
    curl_setopt($curlRequest, CURLOPT_USERPWD, $GLOBALS['API_user'] . ":" . $GLOBALS['API_pass']);
    curl_setopt($curlRequest, CURLOPT_TIMEOUT, 30);
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlRequest, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($curlRequest, CURLOPT_SSL_VERIFYPEER, FALSE);

    // Exec CURL request to fetch the API
    $return = curl_exec($curlRequest);
    // Finish CURL process
    curl_close($curlRequest);

    // Parse the return of CURL from XML to an Array
    $response = new SimpleXMLElement($return);
    // Return the result of the operation and exit
    if ($response->meta->statuscode == 100 && $response->meta->status == "ok") // Double check just in case...
        return TRUE;
    else
        return FALSE;
}
