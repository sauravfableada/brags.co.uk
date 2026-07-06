(function(){'use strict';var COOKIE_NAME='wc_facebook_signals_state';var STATE_ACTIVE='active';var STATE_HELD='held';function setCookie(name,value,days){var d=new Date();d.setTime(d.getTime()+days*86400000);document.cookie=name+'='+encodeURIComponent(value)+';expires='+d.toUTCString()+';path=/;SameSite=Lax'}
function getCookie(name){var match=document.cookie.match(new RegExp('(?:^|;\\s*)'+name+'=([^;]*)'));return match?decodeURIComponent(match[1]):null}
function updateState(state){setCookie(COOKIE_NAME,state,365);var params=wc_facebook_signals_params;return new Promise(function(resolve,reject){var xhr=new XMLHttpRequest();xhr.open('POST',params.ajax_url,!0);xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');xhr.onload=function(){if(xhr.status>=200&&xhr.status<300){try{var data=JSON.parse(xhr.responseText);resolve(data)}catch(e){reject(e)}}else{reject(new Error('AJAX failed: '+xhr.status))}};xhr.onerror=function(){reject(new Error('Network error'))};xhr.send('action='+encodeURIComponent(params.action)+'&security='+encodeURIComponent(params.nonce)+'&state='+encodeURIComponent(state))})}
function hold(){return updateState(STATE_HELD)}
function release(){return updateState(STATE_ACTIVE).then(function(data){if(window.FacebookSignals&&window.FacebookSignals._held){return window.FacebookSignals.release().then(function(){return data},function(){return data})}
return data})}
function getState(){var val=getCookie(COOKIE_NAME);if(val===null){return null}
return val===STATE_ACTIVE?STATE_ACTIVE:STATE_HELD}
window.fbwcsignal=window.fbwcsignal||{};window.fbwcsignal.hold=hold;window.fbwcsignal.release=release;window.fbwcsignal.getState=getState})()
;