<?php
/*
 * Library of custom functions
 * 
/* ------------ Database Connection Functions --------------- */
require '../kint/Kint.class.php';
//Connection to the brettmoa_blog DB for proxy user
function conBlogUser() {
    $server = 'localhost';
    $dbname= '*************';
    $username = '*****************';
    $password = '******************************';
    $dsn = 'mysql:host='.$server.';dbname='.$dbname;
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
    try {
        $conBlog = new PDO($dsn, $username, $password, $options);
    } 
    catch (PDOException $exc) {
        return FALSE;
    }
    if (is_object($conBlog)) {
        return $conBlog;
    }
    return FALSE;
}
//Connection to the guitar1 DB for proxy admin
function conBlogAdmin()  {
    $server = 'localhost';
    $dbname= '********************';
    $username = '**************';
    $password = '********************';
    $dsn = 'mysql:host='.$server.';dbname='.$dbname;
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    try {
        $conBlogadmin = new PDO($dsn, $username, $password, $options);
    } 
    catch (PDOException $exc) {
        return FALSE;
    }

    if (is_object($conBlogadmin)) {
        return $conBlogadmin;
    }
    return FALSE;
}
/* ------------ Password Functions --------------- */
// Use with registration and update (if password is being updated)
function hashPassword($password) {
    $salt = '$6$rounds=9000$' . substr(md5(uniqid(rand(), true)), 0, 16); // SHA-512   
    return crypt($password, $salt); // Result is ~120 character password including salt
}
// Use with login, remember that the password must be queried out of the database first
function comparePassword($password, $hashedPassword)  {
    return crypt($password, $hashedPassword) == $hashedPassword;
}
/* ------------ Data Input Cleanup Functions --------------- */
// Three versions, use the one appropriate for what you want to do
function filterString($string) {
    $string = filter_var(trim($string), FILTER_SANITIZE_STRING); // Encodes special chars
 // $string = filter_var(trim($string), FILTER_SANITIZE_SPECIAL_CHARS); // Removes a small list of special chars
 // $string = filter_var(trim($string), FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Removes all special chars
    return $string;
}
// Always sanitize first (remove potentially bad things), then validate remains for acceptability
function filterNumber($number) {
   $number = filter_var(trim($number), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
   $number = filter_var($number, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND);
   return $number;
}
function validateEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    return $email;
}
/* ------------ Utility Functions --------------- */
function displayFilesInDirectory($directory) {
    echo "<h2>Indv Website Files</h2>
    <table border='5'>
        <colgroup>
        <col span='2' style='background-color:white;'>
        <col span='2' style='background-color:grey;'>
    </colgroup>
    <tr>
        <td><strong>Index:</strong></td>
        <td><strong>Filename: </strong></td>
        <td><strong>Filesize(kbs)</strong></td>
        <td><strong>Last Modified</strong></td>
    </tr>";
    $rowCount=1;
    foreach (glob("$directory*.php") as $path) { 
      // lists all php files in folder 
      $docs[$path] = filectime($path);
    } 

    // sort by value, preserving keys
    asort($docs); 
    foreach ($docs as $path => $timestamp) {
        echo  "<tr>
                   <td>$rowCount</td>
                   <td><a href='$path'>". basename($path)."</a></td>
                   <td>" . filesize($path) . "</td>
                   <td>" . date("d M y h:i:s", $timestamp) . "</td>
               </tr>";
       $rowCount+=1;
    }
   echo "</table>";
}
function buildLinks($array, $displayArchived = false,$level = 0) {
    $HTML = "";
    $HTML .= "<ul class='content_list_level_$level'>";
    foreach ($array as $element) {
        if ($displayArchived || $element['archived'] == false) {
            $HTML .= "<li><a href='.?content="
                  .  str_replace ('Content_Pages/','',$element["content_unique_name"])
                  .  "'>"
                  .  $element["content_title"]
                  .  "</a>";
            if (!empty($element["children"])) {   
                $HTML .= buildLinks($element["children"], false,$level+1);
            }
            $HTML .=  "</li>";                
        }
    }
    $HTML .= "</ul>";       
    return $HTML;     
}
function buildEditContentList($array, $base = true, $padding = 0) {
    $HTML = "";
    if ($base) { 
        $HTML .= "<table id='EditContentList'>"; 
    }    
    foreach ($array as $element) {
        $HTML .= "<tr style='padding-left:$padding.0em'>"
             ."<td><a href='.?content=" . $element["content_unique_name"] . "'>" . $element["content_title"]."</a></td>";        
        if(($element["only_sudo_can_edit"] == true && isSuperAdmin()) || ($element["only_sudo_can_edit"] != true && (isSuperAdmin() || isAdmin()))){
            $HTML .= "<td>&nbsp;<a href='.?action=edit&amp;content=" 
                  . $element["content_unique_name"] . "'>" 
                  . "Edit"."</a>&nbsp;</td>";
            if ($element["archived"] == true){
                $HTML .= "<td>&nbsp;<a href='.?action=unarchive&amp;content=" 
                      . $element["content_unique_name"] . "'>" 
                      . "Unarchive"."</a>&nbsp;</td>";
            }
            else {
                $HTML .= "<td>&nbsp;<a href='.?action=archive&amp;content=" 
                      . $element["content_unique_name"] . "'>" 
                      . "Archive"."</a>&nbsp;&nbsp;&nbsp;</td>";        
            }
        }
        else {
            $HTML .= "<td class='noLeftBorder'>Locked by Sudo&nbsp;</td>"; 
           
            // $HTML .= "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>"; // spacing for "(Unarchive)"
           
        }
       // display the Lock/Unclock Column if the user is a "Super Admin"
        if (isSuperAdmin()) {
            if ($element["only_sudo_can_edit"] == false) {
                $HTML .= "<td>&nbsp;<a href='.?action=SudoAdminLock&amp;content=" 
                      . $element["content_unique_name"] . "'>" 
                      . "Lock Content"."</a>&nbsp;&nbsp;&nbsp;</td>";
            }
            else {
            $HTML .= "<td>&nbsp;<a href='.?action=SudoAdminUnlock&amp;content="  
                  . $element["content_unique_name"] . "'>"  
                  . "Unlock Content"."</a>&nbsp;</td>";
            }
        }    
        $HTML .= "</tr>";                 
        if (!empty($element["children"])) {   
            $HTML .= buildEditContentList($element["children"], false, $padding + 2);
        }
    }
    if ($base) { 
        $HTML .= "</table>"; 
    }
    return $HTML;     
}
function startSecureSession() {
    // prevents A typical session fixation attack like one explained at http://shiflett.org/articles/session-fixation
    session_start(); 
    if (!isset($_SESSION['************************'])) { 
        session_regenerate_id(); 
        $_SESSION['**************************'] = true; 
    } 
}
/********************PATTERN MATCHING FUCNTIONS*/
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== FALSE;
}
/*****************336 GUITAR DB FUNCTIONS***************************/
//Connection to the guitar1 DB for proxy user
function conGtr1User() {
    $server = 'localhost';
    $dbname= '****************************';
    $username = '****************************';
    $password = '****************************';
    $dsn = 'mysql:host='.$server.';dbname='.$dbname;
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    try {
        $congtr1 = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exc) {
        return FALSE;
    }

    if (is_object($congtr1)) {
        return $congtr1;
    }
    return FALSE;
}
//Connection to the guitar1 DB for proxy admin
function conGtr1Admin() {
    $server = 'localhost';
    $dbname= '****************************';
    $username = '****************************';
    $password = '****************************';
    $dsn = 'mysql:host='.$server.';dbname='.$dbname;
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    try {
        $congtr1admin = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exc) {
        return FALSE;
    }

    if (is_object($congtr1admin)) {
        return $congtr1admin;
    }
    return FALSE;
}
//Connection to the guitar2 DB for proxy user
function conGtr2User() {
    $server = 'localhost';
    $dbname= '****************************';
    $username = '****************************';
    $password = '****************************';
    $dsn = 'mysql:host='.$server.';dbname='.$dbname;
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    try {
        $congtr2 = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exc) {
        return FALSE;
    }

    if (is_object($congtr2)) {
        return $congtr2;
    }
    return FALSE;
}
//Connection to the guitar2 DB for proxy admin
function conGtr2Admin() {
    $server = 'localhost';
    $dbname= '****************************';
    $username = '****************************';
    $password = '******************';
    $dsn = "mysql:host=$server; dbname=$dbname";
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

    try {
        $congtr2admin = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exc) {
        return FALSE;
    }

    if (is_object($congtr2admin)) {
        return $congtr2admin;
    }
    return FALSE;
}
function isSuperAdmin()
{
    return($_SESSION['user_type_name'] == 'Super Admin');
}
function isAdmin()
{
    return($_SESSION['user_type_name'] == 'Admin');
}
?>