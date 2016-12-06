<?php
/*
 * This is a PHP file that handle the connection to a MySQL or MariaDB Server
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
 * Details to connect to database
 * -> HOST
 * -> NAME
 * -> USER
 * -> PASS
*/
$dbserver = "database host";
$dbname = "database name";
$dbuser = "database user";
$dbpass = "database password";

/*
 * We are using try - catch because in case of error in the connection control the exception
 */
try {
    $database = new PDO("mysql:host={$dbserver};dbname={$dbname}",
        $dbuser, $dbpass,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")); // Activate UTF-8

    // Prevent SQL Injections
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    // In case of error, show msg and die
    die(sprintf("Fallo en la conexión al servidor de Anthony Cloud (%s)", $e->getMessage()));
}