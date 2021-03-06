<?php
/*
 * Copyright 2015-2016 Fondazione Istituto Italiano di Tecnologia.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


if (isset($_GET["logout"])) {
       // delete the session of the user
        $_SESSION = array();
        session_destroy();
        // return a little feeedback message
        $messages= "You have been logged out.";
} elseif (isset($_POST["login"])) {
    // login via post data (if user just submitted a login form)
    require ('pages/dbaccess.php');
    
    $con = mysqli_connect($hostname, $username, $loginPassword, $dbname);
    
    // /////////////////////////////////////////////////////////////
    // check login form contents
    if (empty($_POST['user_name'])) {
        $errors = "Username field was empty.";
    } elseif (empty($_POST['user_password'])) {
        $errors = "Password field was empty.";
    } elseif (! empty($_POST['user_name']) && ! empty($_POST['user_password'])) {
        
        // Login con LDAP server
        $loginName = $_POST['user_name'];
        $loginPassword = $_POST['user_password'];
                
        $authenticated = FALSE;
        if (isset($HTSFLOW_PATHS['LDAP_URL']) && $HTSFLOW_PATHS['LDAP_URL'] != '') {
                        
            $ldapconn = ldap_connect($HTSFLOW_PATHS['LDAP_URL']);
            
            $uid = "uid=" . $loginName; // . ",ou=lar,ou=Users,ou=People,ou=Accounts,dc=ieo,dc=eu";
            
            if (isset($HTSFLOW_PATHS['LDAP_OU']) && $HTSFLOW_PATHS['LDAP_OU'] != '') {
                foreach (explode (",", $HTSFLOW_PATHS['LDAP_OU']) as $ou) {
                    if ($ou != '') {
                        $uid = $uid . ",ou=" . $ou;
                    }
                }
            }

            if (isset($HTSFLOW_PATHS['LDAP_DC']) && $HTSFLOW_PATHS['LDAP_DC'] != '') {
                foreach (explode (",", $HTSFLOW_PATHS['LDAP_DC']) as $dc) {
                    if ($dc != '') {
                        $uid = $uid . ",dc=" . $dc;
                    }
                }
            }            
            
            error_log( "URL: " . $HTSFLOW_PATHS['LDAP_URL']);
            error_log( "uid: " .$uid);
            
            if ($ldapconn) {
                if (ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3)) { // WITHOUT THIS IS NOT POSSIBLE BIND TO LDAP SERVER
                    try {
                        $authenticated = ldap_bind($ldapconn, $uid, $loginPassword);
                    } catch (Exception $err) {
                        $errors = "Username or password is wrong. Are you from the campus?";
                        $authenticated = 0;
                    }
                } else {
                    $authenticated = 0;
                }
            }
        } else {
            // use db authentication
            $checkQuery = "SELECT password FROM users WHERE user_name = '". $loginName . "' LIMIT 1";

            $res = mysqli_query($con, $checkQuery);
            $line = mysqli_fetch_assoc($res);
            $hash = $line['password'];

            if (password_verify($loginPassword, $hash)) {
                $authenticated = TRUE;
            }
        }
        
        if ($authenticated) {            
            $_SESSION["hf_user_name"] = $loginName;
    
            // then we check that the user is in HTSflow Database users table.
            // if not, it has to be added.
            $checkQuery = "SELECT * FROM users WHERE user_name = \"" . $_SESSION["hf_user_name"] . "\";";

            $res = mysqli_query($con, $checkQuery);
            $line = mysqli_fetch_assoc($res);
            if (is_null($line)) {
                $querySample = "INSERT INTO users (user_name) VALUES (\"" . $_SESSION["hf_user_name"] . "\");";
                $stmt = mysqli_prepare($con, $querySample);
                if ($stmt) {
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    mysqli_stmt_close($stmt);
    
                    $checkQuery = "SELECT * FROM users WHERE user_name = \"" . $_SESSION["hf_user_name"] . "\";";

                    $res = mysqli_query($con, $checkQuery);
                    $line = mysqli_fetch_assoc($res);                    
                    $_SESSION["hf_user_id"] = intval($line["user_id"]);
                } else {
                    $errors = "There was an error in entering your user name in the system. Please contact the administrator.";
                }
            }
            
            $_SESSION['grantedBrowse'] = $line['granted_browse'];
            $_SESSION['grantedPrimary'] = $line['granted_primary'];
            $_SESSION['grantedSecondary'] = $line['granted_secondary'];
            $_SESSION['grantedAdmin'] = $line['granted_admin'];
    
            $_SESSION["hf_user_id"] = intval($line["user_id"]);
        
            $_SESSION['user_login_status'] = 1;
        } else {
            $errors = "Wrong password. Try again.";
        }
    }
}


if (!isset($_SESSION['user_login_status']) || $_SESSION['user_login_status'] == false) {
	header('Location: login.php');
} else if (isset($require_permission)) {
	if ($require_permission == "browse" && $_SESSION['grantedBrowse'] == 0) {
		header('Location: limited_access.php');
	}
	if ($require_permission == "primary" && 0 == $_SESSION['grantedPrimary']) {
		header('Location: limited_access.php');
	}
	if ($require_permission == "secondary" && 0 == $_SESSION['grantedSecondary']) {
		header('Location: limited_access.php');
	}
	if ($require_permission == "admin" && 0 == $_SESSION['grantedAdmin']) {
		header('Location: limited_access.php');
	}
}
