<?php
require_once 'library/library.php';

/********CREATE FUNCTIONS*******************************/
function addContent($content_title, $parent_content_id, $content_text, $author_id) {
  $connection = conBlogUser();
  $lastInsertId = 0;
  try {
    $sql = "INSERT INTO brettmoa_blog.content (content_title, content_text
            , author_id, parent_content_id, created_by, last_updated_by
            , creation_timestamp, last_updated_timestamp) 
            VALUES (:content_title, :content_text
            , :author_id, :parent_content_id, :created_by, :last_updated_by
            , CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':content_title'    , $content_title,     PDO::PARAM_STR);
    $stmt->bindParam(':content_text'     , $content_text,      PDO::PARAM_STR);
    $stmt->bindParam(':parent_content_id', $parent_content_id, PDO::PARAM_INT);
    $stmt->bindParam(':author_id'        , $author_id,         PDO::PARAM_INT);
    $stmt->bindParam(':created_by'       , $author_id,         PDO::PARAM_INT);
    $stmt->bindParam(':last_updated_by'  , $author_id,         PDO::PARAM_INT);
    $stmt->execute();
    $lastInsertId = $connection->lastInsertId();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $lastInsertId;
}
function addUser($firstname, $lastname, $emailaddress, $password)  {
  $connection = conBlogUser();
  $lastInsertId = 0;
  try {
    $sql = "INSERT INTO user (user_first_name, user_last_name, user_email, user_password, user_type_id)  
            VALUES (:user_first_name, :user_last_name, :user_email, :user_password
            , (SELECT user_type_id from user_type where user_type_name = 'User'))";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':user_first_name', $firstname,    PDO::PARAM_STR);
    $stmt->bindParam(':user_last_name',  $lastname,     PDO::PARAM_STR);
    $stmt->bindParam(':user_email',      $emailaddress, PDO::PARAM_STR);
    $stmt->bindParam(':user_password',   $password,     PDO::PARAM_STR);
    $stmt->execute();
    $lastInsertId = $stmt->rowCount();
    $stmt->closeCursor();
    } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $lastInsertId;
}

/********UPDATE FUNCTIONS*******************************/
function updateUniqueNames() {
  $connection = conBlogUser();
  $rowsAffected = 0;
  try {
    $sql = "UPDATE     brettmoa_blog.content child 
            SET   child.content_unique_name = REPLACE(child.content_title,' ','_')
            WHERE child.parent_content_id IS NULL";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    $stmt->closeCursor();
    $sql = "UPDATE     brettmoa_blog.content child 
            INNER JOIN brettmoa_blog.content parent
            ON  (child.parent_content_id = parent.content_id)
            SET child.content_unique_name = CONCAT(parent.content_unique_name, '/', REPLACE(child.content_title,' ','_'))";
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $rowsAffected += $stmt->rowCount();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $rowsAffected;
}
function updateContent($content_id, $content_title, $parent_content_id, $content_text, $author_id) {
  $connection = conBlogUser();
  $rowsAffected = 0;
  try {
    $sql = "UPDATE brettmoa_blog.content 
            SET    content_title = :content_title
            ,      content_text = :content_text
            ,      author_id = :author_id
            ,      parent_content_id = :parent_content_id
            ,      last_updated_by = :last_updated_by
            ,      last_updated_timestamp = CURRENT_TIMESTAMP
            WHERE  content_id = :content_id";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':content_title',     $content_title,     PDO::PARAM_STR);
    $stmt->bindParam(':content_text',      $content_text,      PDO::PARAM_STR);
    $stmt->bindParam(':author_id',         $author_id,         PDO::PARAM_INT);
    $stmt->bindParam(':parent_content_id', $parent_content_id, PDO::PARAM_INT);
    $stmt->bindParam(':last_updated_by',   $author_id,         PDO::PARAM_INT);
    $stmt->bindParam(':content_id',        $content_id,        PDO::PARAM_INT);
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  updateUniqueNames();
  return $rowsAffected;
}
function setContentArchivedById($content_id, $BooleanValue = 1) {
  $connection = conBlogUser();
  $rowsAffected = 0;
  try {
    $sql = "UPDATE     brettmoa_blog.content child 
            RIGHT JOIN brettmoa_blog.content parent
            ON  (child.parent_content_id = parent.content_id)
            SET child.parent_content_id = parent.parent_content_id
            ,   parent.archived = :BooleanValue
            WHERE parent.content_id = :content_id";
    $stmt = $connection->prepare($sql);
    $stmt->bindParam(':content_id',   $content_id,        PDO::PARAM_INT);            
    $stmt->bindParam(':BooleanValue', $BooleanValue,      PDO::PARAM_INT);            
    $stmt->execute();
    $rowsAffected = $stmt->rowCount();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  // updateUniqueNames();
  updateUniqueNames();
  return $rowsAffected;
}
/********READ FUNCTIONS*********************************/
function getContentbyContentUniqueName($content_unique_name) {
  $errorMessage = '<h1>We apologize</h1>'
                . '<p>There was problem retreiving the requested content:'
                . ' ' . htmlspecialchars($content_unique_name) . ' '
                . 'from the server.</p>'
                . '<p>The content may not exist, or the server is currently not responding.</p>';
  try {
    $conn = conBlogUser();
    $sql = " SELECT * 
             FROM content 
             WHERE content_unique_name = :content_unique_name";
    $statement = $conn->prepare($sql);
    $statement->bindParam(':content_unique_name', $content_unique_name, PDO::PARAM_STR);
    $statement->execute(); 
    $content = $statement->fetch();
    $statement->closeCursor();
    
    if (empty($content)) {
        $content = array('content_text' => $errorMessage);
    }
  } 
  catch (Exception $e) {    
    $content = array('content_text' => $errorMessage);
  }
  return $content;
}
function deactivateUserById($user_id)
{
  $conn = conBlogUser();
  $rowsAffected = 0;
  try {
    $sql = "UPDATE brettmoa_blog.user u 
            SET   u.active = false
            WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    $rowsAffected += $stmt->rowCount();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $rowsAffected;
}

function getContentbyID($content_id) {
  $errorMessage = '<h1>We apologize</h1>'
                . '<p>There was problem retreiving the requested content:'
                . ' ' . htmlspecialchars($content_id) . ' '
                . 'from the server.</p>'
                . '<p>The content may not exist, or the server is currently not responding.</p>';
  try {
    $conn = conBlogUser();
    $sql = "SELECT * 
            FROM content 
            WHERE content_id = :content_id";
    $statement = $conn->prepare($sql);
    $statement->bindParam(':content_id', $content_id, PDO::PARAM_STR);
    $statement->execute(); 
    $content = $statement->fetch();
    $statement->closeCursor();
    
    if (empty($content)) {
      return null;
    }
  } 
  catch (PDOException $exc) {    
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $content;
}
function getUserType() {
  try {
    $user_id = $_SESSION['user_id'];
    $conn = conBlogUser();
    $sql = "SELECT user_type_name
            FROM user_type ut
            INNER JOIN user u ON (u.user_type_id = ut.user_type_id)
            WHERE u.user_id = :user_id"; 
    $statement = $conn->prepare($sql);
    $statement->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $statement->execute();
    $array = $statement->fetch();  
    return $array['user_type_name'];  
    } 
  catch (PDOException $exc) 
  { 
  // Send to error page with message
  $message = 'Sorry, there was an internal error with the server.';
  $_SESSION['message'] = $message;
  // header('location: /errordocs/500.php');
  echo "$exc";
  exit;
  }
}
function getParentContentIdDropdown($selected_parent_content_id = null, $exclude_content_id = null) {
  $userType = getUserType();
  $parent_content_id_dropdown = "<select name='parent_content_id' id='parent_content_id'>";           
  if ($userType == 'Super Admin') {
    $parent_content_id_dropdown .= "<option>No Parent </option>";
  }
  if (isset($_SESSION['content_to_edit'])) {
    $content_to_edit_unique_name = $_SESSION['content_to_edit']['content_unique_name'];
    $additonalWhereClasue = " AND content_unique_name NOT LIKE '%".$content_to_edit_unique_name."%' ";
  }
  try {
    $conn = conBlogUser();
    $sql =  "SELECT content_id, parent_content_id
                  , content_title, content_unique_name 
             FROM content "; 
    $sql .= getContentDropDownIDWhereClausebyUserTypeName($userType);  
    if (isset($additonalWhereClasue)) 
    {
        $sql .= $additonalWhereClasue;
    }
    $sql .= " order by content_unique_name ";
    $statement = $conn->prepare($sql);
    $statement->execute();
    $array = $statement->fetchAll();    
    foreach ($array as $value) {
        $parent_content_id_dropdown .= "<option value=".$value["content_id"];
        if ($selected_parent_content_id == $value["content_id"]) { 
            $parent_content_id_dropdown .= " selected ";
        }
        elseif ($selected_parent_content_id == null 
               && $value["content_unique_name"] == 'Content_Pages' 
               && $userType == 'Admin')  {
             $parent_content_id_dropdown .= " selected ";
        }
        $parent_content_id_dropdown .= ">'" 
                                    .  str_replace("/", "'/'",str_replace("_", " ", $value["content_unique_name"]))
                                    .  "'</option>"; 
    }
    $parent_content_id_dropdown .= "</select>";  
    if ($showPagesType == '') {
     $parent_content_id_dropdown = str_replace("'Content Pages'/", "", $parent_content_id_dropdown);
    }
    return $parent_content_id_dropdown;
  } 
  catch (Exception $e) {
    return null;
  }
}

function getContentDropDownIDWhereClausebyUserTypeName($UserType =''){
  if ($UserType == 'Super Admin') {
      return " Where 1 = 1 "; // return all pages
  }
  else { //if ($UserType == 'Admin') {
      return "WHERE content_unique_name LIKE 'Content_Pages%'";
  }
  
  
}


function getContentWhereClausebyUserTypeName($UserType =''){
  if ($UserType == 'User') {
      return "WHERE (content_unique_name LIKE 'User_Pages%'
              OR    content_unique_name LIKE 'Content_Pages%')
              AND   archived = false";  
  } 
  // elseif ($UserType == 'Admin') {
  //     return "WHERE (content_unique_name LIKE 'Content_Pages%'
  //             AND   content_unique_name <> 'Content_Pages')
  //             ";
  // }
  elseif ($UserType == 'Super Admin'|| $UserType == 'Admin') {
      return " Where 1 = 1 "; // return all pages
  }
  else {
      return "WHERE content_unique_name LIKE 'Content_Pages%'"; 
  } 
}
function getContentHierarchyArray($userType = FALSE) {
  if (!($userType)) {
    $userType = getUserType();
  }
  
  try {
    $conn = conBlogUser();
    $sql = "SELECT content_id, parent_content_id, content_title, content_unique_name, archived, only_sudo_can_edit                  
            FROM content ";
    //  Dynamically add Content filter based on userType
    $sql .= getContentWhereClausebyUserTypeName($userType);
    $sql .= " ORDER BY parent_content_id, content_id";
    $dbs = $conn->query($sql);    
    $elem = array();
    // build the PHP result set
    while(($row = $dbs->fetch(PDO::FETCH_ASSOC)) !== FALSE) {
        $row['children'] = array();
        $vn = "row" . $row['content_id'];
        ${$vn} = $row;
        if(!is_null($row['parent_content_id'])) {
            $vp = "parent" . $row['parent_content_id'];
            if(isset($data[$row['parent_content_id']])) {
                ${$vp} = $data[$row['parent_content_id']];
            }
            else {
                ${$vp} = array('content_id' => $row['parent_content_id'], 'parent_content_id' => null, 'children' => array());
                $data[$row['parent_content_id']] = &${$vp};
            }
            ${$vp}['children'][] = &${$vn};
            $data[$row['parent_content_id']] = ${$vp};
        }
        $data[$row['content_id']] = &${$vn};
    }
  $dbs->closeCursor();
  // echo phpinfo();
  // sort the php array
  $result = array_filter($data, function($elem) { return is_null($elem['parent_content_id']); });
  
  return $result;
  } 
  catch (Exception $e) 
  {
    echo 'Exception thrown';
    return null;
  }
}
function getUserByEmail($user_email) {   
  $connection = conBlogUser();
  try {
    $sql = "SELECT u.user_id, u.user_first_name, u.user_last_name,u.user_email, ut.user_type_name, u.user_password,u.active
            FROM user u
            INNER JOIN user_type ut ON (u.user_type_id = ut.user_type_id)
            WHERE u.user_email = :user_email";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':user_email', $user_email,PDO::PARAM_STR);
    $stmt->execute();
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
  } catch (PDOException $exc){
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  
  return $userInfo;
}
function getUserById($user_id) { 
  $connection = conBlogUser();
  try {
    $sql = "SELECT u.*, ut.user_type_name
            FROM user u
            INNER JOIN user_type ut ON (u.user_type_id = ut.user_type_id)
            WHERE u.user_id = :user_id";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':user_id', $user_id,PDO::PARAM_STR);
    $stmt->execute();
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
  } catch (PDOException $exc){
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $userInfo;
}
function doesEmailExist($user_email){
  $connection = conBlogUser();
  try {
    $sql = "SELECT user_email 
            FROM user 
            WHERE user_email = :user_email";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':user_email', $user_email);
    $stmt->execute();
    $existingemail = $stmt->fetch(PDO::FETCH_NUM);
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }

  if(!empty($existingemail)) {
    return TRUE;
  } 
  else {
    return FALSE;
  }
}

function updateUserById($user_id, $first_name, $last_name, $email, $password)
{
  $conn = conBlogUser();
  $rowsAffected = 0;
  try {
    $sql = "UPDATE brettmoa_blog.user u 
            SET   u.user_first_name = :user_first_name
            ,     u.user_last_name = :user_last_name
            ,     u.user_email = :user_email
            ,     u.user_password = :user_password
            WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id',         $user_id,    PDO::PARAM_STR);
    $stmt->bindParam(':user_first_name', $first_name, PDO::PARAM_STR);
    $stmt->bindParam(':user_last_name',  $last_name,  PDO::PARAM_STR);
    $stmt->bindParam(':user_email',      $email,      PDO::PARAM_STR);
    $stmt->bindParam(':user_password',   $password,   PDO::PARAM_STR);
    $stmt->execute();
    $rowsAffected += $stmt->rowCount();
    $stmt->closeCursor();
  } 
  catch (PDOException $exc) {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $rowsAffected;
}



/********DELETE FUNCTIONS********************************/
function deleteCustomer($user_email) {
  try 
  {
    $conn = conBlogUser();
    $sql = "DELETE FROM user u
            WHERE u.user_email = :user_email;";
    $statement = $conn->prepare($sql);
    $statement->execute(array( ':user_email' => $user_email)); 
    $rowsAffected = $stmt->rowCount();
    $statement->closeCursor();
  } 
  catch (PDOException $exc) 
  {
    // Send to error page with message
    $message = 'Sorry, there was an internal error with the server.';
    $_SESSION['message'] = $message;
    // header('location: /errordocs/500.php');
    echo "$exc";
    exit;
  }
  return $rowsAffected;
}
/********GUITAR FUNCTIONS****NOT PART OF FINAL SITE****/


function insertNewCustomer($emailAddress,$password,$firstName,$lastName) {
  $returnVal = 'Insert Failed';
  try {
      $conn = conGuitar2Client();
      $sql = 
      "INSERT INTO customers 
      ( customerID
      , emailAddress
      , firstName
      , password
      , lastName
      , shipAddressID
      , billingAddressID) 
      VALUES 
      ( NULL
      , :emailAddress
      , :firstName
      , :password
      , :lastName
      , NULL
      , NULL)";
      $statement = $conn->prepare($sql);
      $result = $statement->execute(array( ':emailAddress' => $emailAddress
                          , ':password'=> $password 
                          , ':firstName'=> $firstName
                          , ':lastName'=> $lastName)); 
      if ($result == 1) 
      {
        $returnVal = "<p>Customer Identified by $emailAddress inserted.</p>";
      }
      else if 
        ($result == 0) 
      {
        $returnVal = "<p>Insert INTO Customer failed.</p>";
      }
      $statement->closeCursor();
   } 
   catch (Exception $e) 
   {
      $returnVal = "insertNewCustomer() threw an Exception";
   }
     return $returnVal;
}
?>