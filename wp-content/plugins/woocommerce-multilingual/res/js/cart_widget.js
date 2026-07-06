document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (e.target.matches('.wcml_removed_cart_items_clear')) {
            e.preventDefault();
            wcml_cart_clear_removed_items();
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    // It has not been created yet, so the “original” code will build it. There is no need to refresh it.
    if (!sessionStorage.getItem('wc_cart_created')) {
        return;
    }

    var getCookieValue = function (name) {
        return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop() || '';
    }

    // If it was stored in a different language, we need to refresh it because the language has changed.
    if (actions.current_language != sessionStorage.getItem('wcml_current_language')) {
        sessionStorage.setItem('wcml_current_language', actions.current_language);
        actions.force_reset = 1;
    }

    // Check sessionStorage as well as cookies, for backward compatibility.
    var empty_cart_hash = !sessionStorage.getItem('woocommerce_cart_hash')
        && !getCookieValue('woocommerce_cart_hash');

    if (empty_cart_hash || actions.force_reset == 1) {
        setTimeout(wcml_reset_cart_fragments, 0);
    }
});

function wcml_reset_cart_fragments() {
    try {
        document.body.dispatchEvent(new Event('wc_fragment_refresh'));
        sessionStorage.removeItem('wc_fragments');
    } catch (err) { }
}

function wcml_cart_clear_removed_items() {
    var xhr = new XMLHttpRequest();
    var formData = new FormData();

    formData.append('action', 'wcml_cart_clear_removed_items');
    formData.append('wcml_nonce', document.querySelector('#wcml_clear_removed_items_nonce').value);

    xhr.open('POST', woocommerce_params.ajax_url);
    xhr.onload = function() {
        if (xhr.status === 200) {
            window.location = window.location.href;
        }
    };
    xhr.send(formData);
}
