jQuery(function($){
        $.ajax({
    url: ajaxurl,
    data: {
      'action' : 'hc_styles_fetch_society',
      },
    success:function(data) {
	   $("label[for='blog-public']").html('Public and allow search engines to index this site. <br>Note: it is up to search engines to honor your request. <br>The site will appear in public listings around Humanities Commons.');
	   $("label[for='blog-norobots']").html('Public but discourage search engines from index this site. <br>Note: this option does not block access to your site — it is up to search engines to honor your request.<br> The site will appear in public listings around Humanities Commons.');
	   $(".option-site-visibility p.description").remove();
	   $("label[for='blog-norobots']").after("<br />");

	   if(data=='hc') {
	   	$("label[for='blog-private-1']").contents().last()[0].textContent = ' Visible only to registered users of '+data.toUpperCase()+'.';

	   } else {
	   	 $("label[for='blog-private-1']").contents().last()[0].textContent = 'Visible only to registered users of '+data.toUpperCase()+' Commons';
	   }


	   $("label[for='blog-private-2']").contents().last()[0].textContent = 'Visible only to registered users of your site.';

	   $("label[for='blog-private-3']").contents().last()[0].textContent = 'Visible only to administrators of your site.';

    }

  });

});

