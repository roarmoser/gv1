/**
 * @global jQuery
 */
var _chatlio=_chatlio||[];
!function(){var t=document.getElementById("chatlio-widget-embed");if(t&&window.React&&_chatlio.init)return void _chatlio.init(t,React);for(var e=function(t){return function(){_chatlio.push([t].concat(arguments))}},i=["identify","track","show","hide","isShown","isOnline"],a=0;a<i.length;a++)_chatlio[i[a]]||(_chatlio[i[a]]=e(i[a]));var n=document.createElement("script"),c=document.getElementsByTagName("script")[0];n.id="chatlio-widget-embed",n.src="https://w.chatlio.com/w.chatlio-widget.js",n.async=!0,n.setAttribute("data-embed-version","1.1");

	var chat_settings = jQuery.parseJSON( gvFeedback.chat_settings );

	n.setAttribute("data-widget-id","0742416f-7008-4e6f-78d6-e0f0317cbb75");
	n.setAttribute("data-start-hidden", false);
	n.setAttribute('data-chatlio-css-src', chat_settings.css );
	n.setAttribute("data-widget-metadata", gvFeedback.chat_settings );

	c.parentNode.insertBefore(n,c);

	// Don't pass this JSON data to the Slack integration
	delete gvFeedback.chat_settings;

	window._chatlio.identify( 'customer', gvFeedback );
}();
