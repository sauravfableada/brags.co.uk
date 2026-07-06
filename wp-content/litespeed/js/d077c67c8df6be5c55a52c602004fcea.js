(function(){'use strict';var data=(typeof wc_facebook_pixel_data!=='undefined'&&wc_facebook_pixel_data)?wc_facebook_pixel_data:{eventQueue:[]};var firedEvents={};function buildEventData(event){return{method:event.method||'track',name:event.name,params:event.params||{},eventId:event.eventId||null}}
function shouldSkipEvent(eventId){return eventId&&firedEvents[eventId]}
function markEventFired(eventId){if(eventId){firedEvents[eventId]=!0}}
function logWarning(message,data){if(typeof console!=='undefined'&&console.warn){console.warn('[FB Pixel]',message,data)}}
function fireEvent(event){var eventData=buildEventData(event);if(shouldSkipEvent(eventData.eventId)){return}
try{var params=eventData.params;if(window.FacebookSignals&&typeof window.FacebookSignals.trackEvent==='function'){window.FacebookSignals.trackEvent(eventData.name,params,null,eventData.method,eventData.eventId)}else{if(typeof fbq!=='function'){logWarning('fbq not available, skipping event:',eventData.name);return}
if(eventData.eventId){fbq(eventData.method,eventData.name,params,{eventID:eventData.eventId})}else{fbq(eventData.method,eventData.name,params)}}
markEventFired(eventData.eventId)}catch(e){logWarning('Event error: '+eventData.name,e)}}
function fireQueuedEvents(){var events=data.eventQueue;if(!events||!Array.isArray(events)){return}
for(var i=0;i<events.length;i++){try{fireEvent(events[i])}catch(e){logWarning('fireQueuedEvents loop error:',e)}}
data.eventQueue=[]}
function processStoreApiEvent(eventData){if(!eventData||!eventData.event){return}
var params=eventData.params||{};var event={method:'track',name:eventData.event,params:params,eventId:params.event_id||null};fireEvent(event)}
function getRequestUrl(input){if(typeof input==='string'){return input}
if(input&&typeof input.url==='string'){return input.url}
return''}
function isStoreApiAddItemRequest(url){return typeof url==='string'&&(url.indexOf('/wc/store/v1/cart/add-item')!==-1||url.indexOf('/wc/store/cart/add-item')!==-1)}
function isStoreApiBatchRequest(url){return typeof url==='string'&&(url.indexOf('/wc/store/v1/batch')!==-1||url.indexOf('/wc/store/batch')!==-1)}
function processStoreApiResponseData(responseData){if(!responseData||typeof responseData!=='object'){return}
if(responseData.extensions&&responseData.extensions['facebook-for-woocommerce']){processStoreApiEvent(responseData.extensions['facebook-for-woocommerce'])}else if(Array.isArray(responseData.responses)){for(var i=0;i<responseData.responses.length;i++){var item=responseData.responses[i];if(item&&item.body&&item.body.extensions&&item.body.extensions['facebook-for-woocommerce']&&item.status>=200&&item.status<300){processStoreApiEvent(item.body.extensions['facebook-for-woocommerce'])}}}}
function setupFetchInterceptor(){var originalFetch=window.fetch;if(!originalFetch){return}
window.fetch=function(){var args=arguments;var url=getRequestUrl(args[0]);var isAddToCartRequest=isStoreApiAddItemRequest(url);var isBatchRequest=isStoreApiBatchRequest(url);return originalFetch.apply(this,args).then(function(response){if((isAddToCartRequest||isBatchRequest)&&response.ok){response.clone().json().then(function(responseData){processStoreApiResponseData(responseData)}).catch(function(e){logWarning('Store API JSON parse error:',e)})}
return response})}}
function init(){setupFetchInterceptor();if(typeof fbq==='function'){fireQueuedEvents();return}
var _fbq=window.fbq;Object.defineProperty(window,'fbq',{configurable:!0,enumerable:!0,get:function(){return _fbq},set:function(value){_fbq=value;if(typeof value==='function'){Object.defineProperty(window,'fbq',{configurable:!0,enumerable:!0,writable:!0,value:value});setTimeout(fireQueuedEvents,0)}}})}
if(document.readyState==='complete'){init()}else{window.addEventListener('load',init)}})()
;