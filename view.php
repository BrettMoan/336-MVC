<!DOCTYPE html>
<html> 
<!-- BLOG TEMPLATE -->
<head>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Brett Moan's Blog</title>
    <!-- PERSONAL CSS -->
    <link href="css/blog.css" rel="stylesheet" type="text/css" media="screen" />
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
    <!-- DEPENDANCIES -->
    <?php include 'modules/dependancies.php'; ?>    
</head>

<body>

<!-- ********** HEADER ******** -->
<?php include 'modules/header.php'; ?> 
<!-- ********** MAIN CONTENT ******** -->
<div id = "Content"><?php  
if (isset($_SESSION['message']))
{
    $message = $_SESSION['message'];
    echo "<h4>" . $message . "</h4>";
}

// TODO: implement Security check to only run <?PHP code
//       or <script> code if it was was verified by a 'Super Admin'

// security check is not required as part of 336 requirments
if (!empty($content)) // && $content['checked'] ) 
{
    if (!$content['allow_php_eval']) {
        echo eval("?>".html_entity_decode($content['content_text'])."<?"); 
    }
    else
    {
        $temp =  html_entity_decode($content['content_text']) ;
        echo $temp;
    }
    
    // echo html_entity_decode($content['content_text']);
}
else
{
    echo $linksList;
}
?>
<a href="#" class="back-to-top">Back to Top</a>
</div>
<!-- Close the "Content" -->
<!-- *******RIGHT NAVIGATION AND FOOTER***** -->
<?php include 'modules/footer.php'; ?>
<script type="text/javascript">SyntaxHighlighter.all();</script>
<script type="text/javascript">lineWrap();</script>
<?php 
    include 'modules/blogNav.php';  
    $_SESSION['message'] = null;
?>
</body>
</html>


