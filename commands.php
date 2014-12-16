<?php
// index.php + commands.php make up my controller in the MVC design pattern
require_once 'model.php';

function processInsertOrUpdateContent() {

    // Check the data
    // Process the login attempt
    // Get Data
    $content_id = filterNumber($_POST['content_id']);
    $content_title = filterString($_POST['content_title']);
    $parent_content_id = filterNumber($_POST['parent_content_id']);
    $content_text = htmlentities($_POST['content_text']);        
    $author_id =  filterNumber($_SESSION['user_id']);
    // $checked = ('(<?', $_POST['content_text']);
    $_SESSION['content_to_add'] = null;
    $action = filterString($_POST['action']);

    if ($action == 'SyncronizeContent') {
        $insertOrUpdate  = "insert";
        $sendingPage = 'Admin_Pages/Add_Content_Page';
    } 
    elseif ($action == 'UpdateContent') {
        $insertOrUpdate  = "update";
        $sendingPage = 'Admin_Pages/Edit_Content';
    } 
    if ($parent_content_id < 1) { 
        $parent_content_id = null; 
    }
    if(empty($content_title)) {
        $message = 'The post must have a title.';
    }

    //TODO: check unique_content_name


    // If errors, return for repair
    if(isset($message)) {
        $_SESSION['content_to_add'] 
                 = array(  'content_title' => $content_title
                        ,  'parent_content_id' => $parent_content_id
                        ,  'content_text' => $content_text
                        ); 
        $_GET['content'] = $sendingPage;
        displayContent($message);
        exit;
    }
    if ($insertOrUpdate == 'insert') 
    {
        $insert_id = addContent($content_title, $parent_content_id, $content_text, $author_id);
    } elseif ($insertOrUpdate == 'update') 
    {
        $rowsAffected = updateContent($content_id, $content_title, $parent_content_id, $content_text, $author_id);
    }
    
    if ($insert_id > 0) 
    { $message = "Content $content_title added." ;} 
    elseif  ($rowsAffected == 1)  
    { 
        $message = "Content $content_title modified.";
        $_SESSION['content_to_edit'] = null;
    }
    else 
    { $message = "Content $insertOrUpdate Failed." ;} 
    
    $_SESSION['message'] = $message;

    header('Location: .?&content=Admin_Pages/Manage_Content');
    // $_GET['content'] = 'Admin_Pages/Manage_Content';
    // displayContent($message);
}
function processLogin() {
    // Process the login attempt
    // Get Data
    $email_address = validateEmail($_POST['emailaddress']);
    $password =     filterString($_POST['password']);

    // Check the data
    if(empty($email_address) || empty($password)){
        $message = 'You must supply an email address and password.';
    }
    // If errors, return for repair
    if(isset($message)) {
        // TODO: 
        $_GET['content'] = 'User_Pages/Login_Page';
        displayContent($message);   
        exit;
    }
    // Proceed with login attempt, if no errors
    // Get the data from the database based on the email address
    
    $loginData = getUserByEmail($email_address); 
    // var_dump($loginData);
    $hashedPassword = $loginData['user_password'];
    // Compare the passwords for a match
    $passwordMatch = comparePassword($password, $hashedPassword);
    // If there is a match, do the login
    if (!($loginData['active'])) {
        $message = 'This account has been deactivated.';
    }

    if(isset($message)) {
        // TODO: 
        $_GET['content'] = 'User_Pages/Login_Page';
        displayContent($message);   
        exit;
    }

    if($passwordMatch) {
        // Use the session for login data
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_first_name'] = $loginData['user_first_name'];
        $_SESSION['user_last_name']  = $loginData['user_last_name'];
        $_SESSION['user_id']         = $loginData['user_id'];
        $_SESSION['user_type_name']  = $loginData['user_type_name'];
        $_SESSION['user_info']  = $loginData;
        
        // Indicate that the login was a success
        $message = $loginData['user_first_name'].', you have logged in.';

        displayContent($message);   
        exit;
    } 
    else {
        // There was not a match, tell the user 
        $message = 'Login attempt failed. Check your login information.';
        // TODO: 
        $_GET['content'] = 'User_Pages/Login_Page';
        displayContent($message);   
        exit;
    }
}
function logoutUser() {
    // Process the logout
    // Remove the login data from the session
    $_SESSION['loggedin'] = FALSE;
    $_SESSION['user_first_name'] = null;
    $_SESSION['user_last_name']  = null;
    $_SESSION['user_id']         = null;
    $_SESSION['user_type_name']  = null;
    $_SESSION['user_info']  = null;
    session_destroy();
    // send to home page
    header('location: .');
}
function registerUser() {
    // Process the registraation
    // Collect data
    $firstname = filterString($_POST['first_name']);
    $lastname = filterString($_POST['last_name']);
    $email = validateEmail($_POST['email']);
    $password = filterString($_POST['password']);
    $password_two = filterString($_POST['password_two']);
    // validate the data
    if(empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($password_two))
    {
    // message to tell the registrant something is wrong
        $message = 'All fields are required. Please make sure that all fields have valid entries.';
    }
    // Check for errors, return to be fixed
    if(isset($message))
    {
        $_SESSION['registration_array'] = array(  'first_name' => $firstname
                                               ,  'last_name' => $lastname
                                               ,  'email' => $email 
                                               ); // use to repopulate the form
        $_GET['content'] = $_POST['sending_page'];
        displayContent($message); // Send back to register for repair
        exit; // stop all further processing on this page
    }
    
    // No errors found, process the registration
    // Check for existing email address
    $existingEmail = doesEmailExist($email);
    
    if ($existingEmail) 
    {
        $message = "Sorry, you cannot register using the provided Email address, please choose another or try <a href=\".?action=login\" title='Go to login page'>logging in</a>.";
        $_SESSION['registration_array'] = array(  'first_name' => $first_name
                                               ,  'last_name' => $last_name
                                               ,  'email' => $email 
                                               ); // use to repopulate the form
        $_GET['content'] = $_POST['sending_page'];
        displayContent($message); // Send back to register for repair
        exit; // stop all further processing on this page
    }
    

    // No prior email address found, proceed with the registration
    $password = hashPassword($password); // hash the password
    $insertResult = addUser($firstname, $lastname, $email, $password);
    // Find out the result, notify client
    if ($insertResult) {
        $loginData = getUserByEmail($email); 
            // Use the session for login data
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_first_name'] = $loginData['user_first_name'];
        $_SESSION['user_last_name']  = $loginData['user_last_name'];
        $_SESSION['user_id']         = $loginData['user_id'];
        $_SESSION['user_type_name']  = $loginData['user_type_name'];
        $_SESSION['user_info']  = $loginData;
        $message = '<p class="notice">Thank you ' . $loginData['user_first_name'] . ' you have been registered.</p>';
    } 
    else 
    {
        $message = '<p class="notice">Sorry, ' . $firstname . ' the registration failed.</p>';
    }

    displayContent($message); 
}
function displayContent($message = null) {
    if (isset($_POST['content'])) {
        $contentReceived = filterString($_POST['content']);
    }
    elseif (isset($_GET['content'])) {
        $contentReceived = filterString($_GET['content']);
    }

    if (startsWith($contentReceived, 'Admin_Pages') && !(isSuperAdmin()||isAdmin())) {
        header('location: .');
    }
    
    $content = getContentbyContentUniqueName($contentReceived);
    if (empty($content['content_id'])) {
        $contentReceived = 'Content_Pages/'. $contentReceived;       
        $content = getContentbyContentUniqueName($contentReceived);
    }

    // if (isset($contentReceived)) {
    //     if (!startsWith($contentReceived, 'Admin_Pages') 
    //      && !startsWith($contentReceived, 'User_Pages') 
    //      && !startsWith($contentReceived, 'Content_Pages') 
    //      && !startsWith($contentReceived, 'Site_Plan')) {
    //         $contentReceived = 'Content_Pages/'. $contentReceived;       
    //     }
        
        // $content = getContentbyContentUniqueName($contentReceived);

    // }
    if (isset($message)) {
        $_SESSION['message'] = $message;
    }
    $linksList = getLinksList();
    include 'view.php';
}
function getLinksList() {
    $array = getContentHierarchyArray(); // gets all items
    foreach ($array as $key => $value) {
        if($value['content_unique_name'] == 'Content_Pages'){
            $Content_Pages_Children = $value['children'];
        }          
    }
    return buildLinks($Content_Pages_Children);
}
function getAdminLinksList() {      
    $array = getContentHierarchyArray('Super Admin'); // gets all items
    foreach ($array as $key => $value) {
        if($value['content_unique_name'] == 'Admin_Pages'){
            $Content_Pages_Children = $value['children'];
        }          
    }
    return buildLinks($Content_Pages_Children);
}
function deactivateUser(){
    if($_SESSION['loggedin'] = TRUE)
    {
        deactivateUserById($_SESSION['user_id']); 
    }
    logoutUser();
}
function ArchiveOrUnArchiveContent($BooleanValue){
    if ($_SESSION['user_type_name'] != 'Admin' && $_SESSION['user_type_name'] != 'Super Admin') {
        header('location: .');
    }
    elseif (isset($_POST['content'])) {
        $contentsent = filterString($_POST['content']);
    }
    elseif (isset($_GET['content'])) {
        $contentsent = filterString($_GET['content']);
    }
    else {
       header('location: .');   
    }
    $content_to_alter = getContentbyContentUniqueName($contentsent);
    // kint::dump($content_to_alter['content_id']);
    setContentArchivedById($content_to_alter['content_id'], $BooleanValue);
    
    $_SESSION['message'] = $message;
    header('Location: .?&content=Admin_Pages/Manage_Content');
}
function ArchiveContent(){
   ArchiveOrUnArchiveContent(1);
}
function UnArchiveContent(){
    ArchiveOrUnArchiveContent(0);
}
function getAdminEditContentList() {
    $array = getContentHierarchyArray();
    return buildEditContentList($array);       
}
function runShowPages() {
    displayFilesInDirectory('');
}
function displayAdminEditContentList() {   
    $_GET['content'] = 'Admin_Pages/Manage_Content';
    displayContent($message);   
}
function getContentToEdit(){
    if (isset($_POST['content'])) {
        $contentsent = filterString($_POST['content']);
    }
    elseif (isset($_GET['content'])) {
        $contentsent = filterString($_GET['content']);
    }
    $content_to_edit = getContentbyContentUniqueName($contentsent);
    if (($content_to_edit['only_sudo_can_edit'] && !isSuperAdmin()) || (!isSuperAdmin() && !isAdmin())) {
        header('Location: .');
    }
    // TODO: Do stuff with the content
    $_SESSION['content_to_edit'] = $content_to_edit;
    $_GET['content'] = 'Admin_Pages/Edit_Content';

    displayContent($message);   
}
function getLoginPage(){
      $_GET['content'] = 'User_Pages/Login_Page';
      displayContent($message); // Send back to register for repair
}
function updateUserInfo(){
    $sending_page = filterString($_POST['sending_page']);
    $user_id = filterString($_POST['user_id']);

    $first_name = filterString($_POST['first_name']);
    $last_name = filterString($_POST['last_name']);
    $email = validateEmail($_POST['email']);
    $password = filterString($_POST['password']);
    $password_two = filterString($_POST['password_two']);


    if(empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($password_two)){
    // message to tell the registrant something is wrong
        $message = 'All fields are required. Please make sure that all fields have valid entries.';
    }
    elseif  ($password != $password_two){
        $message = 'Password entries one and two must match. Please Try Again.';   
    }
    
    if(isset($message))
    {
        $_GET['content'] = $_POST['sending_page'];
        displayContent($message); // Send back to register for repair
        exit; // stop all further processing on this page
    }
    
    // No errors found, process the registration
    // Check for existing email address
    $existingEmail = doesEmailExist($email);
    
    if ($existingEmail) 
    {
        $loginData =  getUserByEmail($email);
        if ($loginData['user_id'] != $user_id) {
            $message = "Sorry, you cannot use the provided Email address, It is already in use. contact an administrator if you think there is an error.";
            $_GET['content'] = $_POST['sending_page'];
            displayContent($message); // Send back to register for repair
            exit; // stop all further processing on this page
        }
        
    }
    // No prior email address found, or email address is from the current user.
    $password = hashPassword($password); // hash the password
    $updateResult = updateUserById($user_id, $first_name, $last_name, $email, $password);
    // Find out the result, notify client
    if ($updateResult) {
        $loginData = getUserByEmail($email); 
        // Use the session for login data
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['user_first_name'] = $loginData['user_first_name'];
        $_SESSION['user_last_name']  = $loginData['user_last_name'];
        $_SESSION['user_id']         = $loginData['user_id'];
        $_SESSION['user_type_name']  = $loginData['user_type_name'];
        $_SESSION['user_info']  = $loginData;
        $message = '<p class="notice">Thank you ' . $loginData['user_first_name'] . ' your information has been updated.</p>';
    } 
    else 
    {
        $message = '<p class="notice">Sorry, ' . $firstname . ' the update failed.</p>';
    }
    $_GET['content'] = $_POST['sending_page'];
    displayContent($message); 
}

$commands = array( 
                   'SyncronizeContent' => 'processInsertOrUpdateContent'
                 , 'UpdateContent' => 'processInsertOrUpdateContent'
                 , 'Login' => 'processLogin'
                 , 'login' => 'getLoginPage'
                 , 'logout' => 'logoutUser'
                 , 'register' => 'registerUser'
                 , 'getContent' => 'displayContent'
                 , 'archive' => 'ArchiveContent'
                 , 'unarchive' => 'UnArchiveContent'
                 // , 'login' => 'getLogin'
                 , 'update_user'=> 'updateUserInfo'
                 , 'edit' => 'getContentToEdit'
                 , 'showpages' => 'runShowPages' 
                 , 'adminEdit' => 'getAdminEditContentList' 
                 , 'default' => 'displayContent'
                 , 'deactivate' => 'deactivateUser'
                 );
?>