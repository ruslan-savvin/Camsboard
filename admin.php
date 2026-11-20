<!DOCTYPE html>
<html>

<head>
    <meta name="robots" content="noindex">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title></title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/images/favicon/site.webmanifest">
    <link rel="mask-icon" href="/images/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="/css/adminPart/constants.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/base.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/page.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/calendar.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/table.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/icon.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/navbar.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/select.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/input.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/login.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/fontstyles.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/button.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/modal.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/indicatorCircular.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/indicatorLine.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/card.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/img.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/checkbox.css" type="text/css">
    <link rel="stylesheet" href="/css/adminPart/inputFile.css" type="text/css">

</head>

<body>
    <div id="dropdown" class="dropdown"></div>
    <div class="container-nav">
        <img class="logo" src="/images/icons/logo.png" srcset="/images/icons/placeholder.webp" data-srcset="/images/icons/logo.png" alt="logo">
        <input type="checkbox" name="menu" id="menuburger" data-menuburger>
        <label for="menuburger" class="menu-burger nav-div-hidden" data-menuburger>
            <span data-menuburger></span>
            <span data-menuburger></span>
            <span data-menuburger></span>
        </label>
        <nav>
            <div id="buttonNav1" data-translate="global.nav.action" class="nav-div-hidden"></div>
            <div id="buttonNav2" data-translate="global.nav.selectDevice" class="nav-div-hidden"></div>
            <div id="buttonNav3" class="nav-div-hidden">
                <div id="buttonNav31"></div> &triangledown;
            </div>
            <div id="adminOutput" data-translate="global.nav.out" class="nav-div-hidden"></div>
        </nav>
    </div>
    <div id='indicatorBook' class='indicator-book-none'>
        <div id='circularG_1' class='circularG'></div>
        <div id='circularG_2' class='circularG'></div>
        <div id='circularG_3' class='circularG'></div>
        <div id='circularG_4' class='circularG'></div>
        <div id='circularG_5' class='circularG'></div>
        <div id='circularG_6' class='circularG'></div>
        <div id='circularG_7' class='circularG'></div>
        <div id='circularG_8' class='circularG'></div>
    </div>
    <div id="pageVideo" class="page page-left page-hidden">
        <div id="deviceName1" class="page-header"></div>
        <iframe id="iframe1"
            style="width: 100%; border: none; background: #000;"
            allow="autoplay; fullscreen"
            allowfullscreen>
        </iframe>
        <div class="page-headers-row">
            <div class="page-header">Yolo </div>
            <div id="tester1" class="page-header"></div>
        </div>
        <div class="page-headers-row">
            <div class="page-header">Cleaner </div>
            <div id="tester2" class="page-header"></div>
        </div>
    </div>
    <div id="page4" class="page page-right page-hidden">
        <div id="deviceName2" class="page-headers-row">
            <div id="deviceName21" class="page-header"></div>
            <div id="deviceName22" class="page-header"></div>
        </div>
        <div id="tableAttachmentPointevents"></div>
    </div>
    <div id="page5" class="page page-right page-hidden">
        <div data-translate="forma.systemSettings.inscription.settings" class="page-header"></div>
        <button id="buttonSaveGeneralSettings" data-translate="forma.global.button.save" class="standard-btn"></button>
        <div id="page5-fields" class="page-fields"></div>
    </div>
    <div id="page6" class="page page-right page-hidden">
        <div data-translate="forma.reference.inscription.devices" class="page-header"></div>
        <div id="tableAttachmentPointdevices"></div>

        <div data-translate="forma.reference.inscription.languages" class="page-header"></div>
        <div id="tableAttachmentPointlanguages"></div>

        <div data-translate="forma.reference.inscription.users" class="page-header"></div>
        <div id="newUserLink" class="page-header"></div>
        <div id="tableAttachmentPointusers"></div>

        <div data-translate="forma.reference.inscription.tasks" class="page-header"></div>
        <div id="tableAttachmentPointtasks"></div>
    </div>
    <div id="page9" class="page page-right page-hidden ">
        <div id="tableAttachmentPointlog"></div>
    </div>
    <div id="page11" class="login-container login-container-hidden">
        <form id="blocklogin1" class="login-block login-block-hidden">
            <fieldset id="fieldBlocklogin1" class="fieldset fieldset-shadow">
                <legend id="legendEmail" data-translate="forma.authorization.inscription.login" class="legend"></legend>
                <input id="email" type="text" class="input-fieldset" autocomplete="on">
            </fieldset>
            <button id="buttonGetPas" type="submit" data-translate="forma.authorization.button.login" class="standard-btn"></button>
        </form>
        <form id="blocklogin2" class="login-block login-block-hidden">
            <fieldset class="fieldset fieldset-shadow">
                <legend id="fegrvt" data-translate="forma.authorization.inscription.dynamicPassword" class="legend"></legend>
                <input id="code" type="text" class="input-fieldset" autocomplete="on">
            </fieldset>
            <button id="buttonSetAuthorization" type="submit" data-translate="forma.authorization.button.authorize" class="standard-btn"></button>
            <button id="buttonPasswordReceived" data-translate="forma.authorization.button.didNotReceivePassword" class="standard-btn"></button>
        </form>
    </div>

    <script type="module" src="/js/admin/Main.js" id="main"></script>
</body>

</html>