<?php

function custom_cookie_consent_popup() {
    ?>
    <!-- Cookie Consent Popup -->
    <div id="cookie-popup">
        <div class="cookie_popup">
            <div class="left-side-contend">
                <p>We use cookies to improve your experience on our site, analyse traffic and deliver personalised content. By continuing to browse, you agree to our use of cookies. For more information, please see our <a href="https://brags.co.uk/cookies-policy/">Cookie Policy</a>.</p>
            </div>
            <div class="right-side-btn">
                <button id="accept-cookies">Accept Cookies</button>
            </div>
        </div>
    </div>

    <style>
        .cookie_popup {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cookie_popup .left-side-contend {
            width: 77%;
        }
        .cookie_popup .left-side-contend p {
            margin-bottom: 0;
        }
        .cookie_popup .right-side-btn {
            width: 22%;
        }
        #cookie-popup {
            position: fixed;
            bottom: 0px;
            left: 0;
            width: 100%;
            right: 0;
            background: #222222f0;
            color: #fff;
            padding: 30px 40px;
            border-radius: 1px;
            display: none;
            z-index: 9999;
        }
        #cookie-popup a {
            color: #ffd700;
            text-decoration: underline;
        }
        #accept-cookies {
            background: #ff9800;
            color: #fff;
            border: none;
            padding: 10px 25px;
            float: right;
            cursor: pointer;
            border-radius: 3px;
            margin-left: 10px;
        }
        #accept-cookies:hover {
            background: #e68900;
        }
        @media only screen and (max-width: 767px) {
            #accept-cookies {
                margin-left: 0;
                width: 100%;
                float: inherit;
                margin-top: 20px;
            }
            .cookie_popup {
                flex-direction: column;
            }
            .cookie_popup .right-side-btn,
            .cookie_popup .left-side-contend {
                width: 100%;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            if (!localStorage.getItem("cookieConsent")) {
                $("#cookie-popup").fadeIn();
            }

            $("#accept-cookies").click(function() {
                localStorage.setItem("cookieConsent", "true");
                $("#cookie-popup").fadeOut();
            });
        });
    </script>
    <?php
}

add_action('wp_footer', 'custom_cookie_consent_popup');