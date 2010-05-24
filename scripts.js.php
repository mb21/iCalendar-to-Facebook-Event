<?php require_once('config.php');
//for JavaScript syntax highlighting:
if(0) { ?><script type="text/javascript"><?php } ?>


	//0 is none, -1 is new subscription, positive number is sub_id
	which_options_open = 0;

	function do_submit(formname, posturl, rewriteid) {
		var div_id = document.getElementById(rewriteid);
		var form = document.getElementById(formname);

		//display loading bar
		div_id.setInnerXHTML('<img src="<?php echo _HOST_URL; ?>loader.gif" alt="Loading..."/>');

		var ajax = new Ajax();
		ajax.responseType = Ajax.JSON;
		ajax.requireLogin = true;
		ajax.ondone = function(data) {
			div_id.setInnerXHTML(data.msg);
			if (data.sub_name){
				insertSub(data);
			}
			else if (data.adv_sub_name){
				//update subscription list
				var row = document.getElementById('sub_' + data.sub_id).getChildNodes();
				row[0].setInnerXHTML("<h3 title='" + data.url + "'>" + data.adv_sub_name + "</h3>");
				row[1].setInnerXHTML("<p>" + data.adv_category + "</p>");
				row[2].setInnerXHTML("<p>" + data.adv_subcategory + "</p>");
				row[4].setInnerXHTML('<p><a href="#">Edit</a></p>');
				data.category = data.adv_category;
				data.subcategory = data.adv_subcategory;
				data.sub_name = data.adv_sub_name;
				row[4].purgeEventListeners('click');
				row[4].addEventListener('click', function() { show_options(data) });
			}
		}
		var formdata = form.serialize();
		ajax.post(posturl, formdata);
	}

	function insertSub(json_sub){
		var table = document.getElementById('subscription_table');
		var row = document.createElement('tr');
		row.setId('sub_'+json_sub.sub_id);

		var sub_name = document.createElement('td');
		row.appendChild(sub_name);
		sub_name.setInnerXHTML("<h3 title='"+ json_sub.url +"'>" + json_sub.sub_name + "</h3>");

		var category = document.createElement('td');
		row.appendChild(category);
		category.setInnerXHTML("<p>" + json_sub.category + "</p>");

		var subcategory = document.createElement('td');
		row.appendChild(subcategory);
		subcategory.setInnerXHTML("<p>" + json_sub.subcategory + "</p>");

		var group = document.createElement('td');
		row.appendChild(group);
		if (json_sub.page_id == 0){
			var text = "<p>\u2013</p>";
		}
		else{
			var text = '<p><a target="_blank" href="http://www.facebook.com/group.php?gid=' + json_sub.page_id + '">' + json_sub.page_id + '</a></p>';
		}
		group.setInnerXHTML(text);

		var edit = document.createElement('td');
		row.appendChild(edit);
		edit.setInnerXHTML('<p><a href="#">Edit</a></p>');
		edit.addEventListener('click', function() { show_options(json_sub) });

		var remove = document.createElement('td');
		row.appendChild(remove);
		remove.setInnerXHTML('<p><a href="#">Unsubscribe</a></p>');
		remove.addEventListener('click', function() { show_unsubscribe(json_sub.sub_id, json_sub.sub_name ) });

		table.appendChild(row);
	}

	function removeSub(sub_id){
		var row = document.getElementById('sub_'+sub_id);
		document.getElementById('subscription_table').removeChild(row);
	}

	function toggle_view(id, siteHeight){
		var search=document.getElementById(id).getStyle('display');
		if (search == 'block') {
			document.getElementById(id).setStyle('display','none');
			document.getElementById("subscriptions").setStyle('minHeight', '');
		}
		else{
			if (!siteHeight){
				var siteHeight = '';
			}
			document.getElementById(id).setStyle('display','block');
			document.getElementById("subscriptions").setStyle('minHeight', siteHeight);
		}
	}

	function show_options(json_sub){
		if (!json_sub){
			var text = "<h3>Options for the new subscription</h3>";
			which_options_open = -1;

			document.getElementById("adv_page").setStyle('display','block');
			document.getElementById("adv_subname").setStyle('display','none');
			document.getElementById("adv_cats").setStyle('display','none');
			document.getElementById("adv_cancel").setStyle('display','none');
			document.getElementById("adv_update").setStyle('display','none');
			document.getElementById("wall").setValue('off');
		}
		else{
			var text = "<h3>Options for <i>"+json_sub.sub_name+"</i></h3>";
			which_options_open = json_sub.sub_id;

			document.getElementById("adv_page").setStyle('display','none');
			document.getElementById("adv_sub_id").setValue(json_sub.sub_id);
			document.getElementById("adv_sub_name").setValue(json_sub.sub_name);
			if(json_sub.page){
				document.getElementById("adv_page").setValue(json_sub.page);
			}
			document.getElementById("adv_category_select").setValue(json_sub.category);
			category_change('adv_');
			document.getElementById("adv_subcategory_select").setValue(json_sub.subcategory);
			if (json_sub.wall == "0")
				var wall = false;
			else
				var wall = true;
			document.getElementById("wall").setChecked(wall);
			
		}
		document.getElementById("option_title").setInnerXHTML(text);
		toggle_view("options", "30em");
	}

	function close_options(){
		//check permissions
		var div_id = document.getElementById('adv_msg_div');
		var form = document.getElementById('sub_form');
		div_id.setInnerXHTML('<img src="<?php echo _HOST_URL; ?>loader.gif" alt="Loading..."/>');

		var ajax = new Ajax();
		ajax.responseType = Ajax.JSON;
		ajax.requireLogin = true;
		ajax.onerror = function() {
			div_id.setInnerXHTML('<div class="clean-error">No response from server. Please try again.</div>');
		}
		ajax.ondone = function(data) {
			if (data.msg == "success"){
				if (which_options_open > 0){
					//send update to server
					do_submit('sub_form', '<?php echo _HOST_URL . 'update_sub.php'; ?>','messages');
				}
				else{
					document.getElementById("adv_subname").setStyle('display','block');
					document.getElementById("adv_cats").setStyle('display','block');
					document.getElementById("adv_cancel").setStyle('display','inline');
					document.getElementById("adv_update").setStyle('display','block');
					document.getElementById("messages").setInnerXHTML("<span></span>");
				}
				div_id.setInnerXHTML("<span></span>");
				which_options_open = 0;
				toggle_view("options");
			}
			else{
				if (data.msg == "publish")
					div_id.setInnerFBML(perms_publish);
				else if(data.msg == "rsvp")
					div_id.setInnerFBML(perms_rsvp);
			}
		}
		var formdata = form.serialize();
		ajax.post('<?php echo _HOST_URL . 'update_sub_check.php'; ?>', formdata);
	}

	function show_unsubscribe(sub_id, sub_name){
		//put sub_id in hidden input field
		document.getElementById('unsub_sub_id').setValue(sub_id);

		var id = "unsubscribe_dialog";
		//set text in dialog
		var text = "<p>Are you sure you want to unsubscribe from calendar <i>"+sub_name+"</i>? Do you also want to remove all events of that subscription from facebook?</p>";
		document.getElementById("unsub_text").setInnerXHTML(text);
		text = "<h2 class=\"dialog_head_text\">Unsubscribe from calendar <i>"+sub_name+"</i></h2>";
		document.getElementById("unsub_head").setInnerXHTML(text);

		//make dialog visible
		document.getElementById(id).setStyle('display','block');
	}

	function unsubscribe(mode) {
		document.getElementById('unsubscribe_dialog').setStyle('display','none');

		var posturl = '<?php echo _HOST_URL; ?>unsubscribe.php';
		var div_id = document.getElementById('messages');
		var form = document.getElementById('unsub_form');

		//display loading bar
		div_id.setInnerXHTML('<img src="<?php echo _HOST_URL; ?>loader.gif" alt="Loading..."/>');

		var ajax = new Ajax();
		ajax.responseType = Ajax.JSON;
		ajax.requireLogin = true;
		ajax.ondone = function(data) {
			div_id.setInnerXHTML(data.msg);
			if (data.success){
				removeSub(data.sub_id);
			}
		}
		document.getElementById('unsub_mode').setValue(mode);
		var formdata = form.serialize();
		ajax.post(posturl, formdata);
	}

	function category_change(adv) {
		//Drop down category lists

		if (adv){
			var cat = 'adv_category_select';
			var subcat = 'adv_subcategory_select';
		}
		else{
			var cat = 'category_select';
			var subcat = 'subcategory_select';
		}
		cat = document.getElementById(cat);
		subcat = document.getElementById(subcat);

		switch ( cat.getValue() ){
			case "1":
				subcat.setInnerFBML(cats_string.e1);
				break;
			case "2":
				subcat.setInnerFBML(cats_string.e2);
				break;
			case "3":
				subcat.setInnerFBML(cats_string.e3);
				break;
			case "4":
				subcat.setInnerFBML(cats_string.e4);
				break;
			case "5":
				subcat.setInnerFBML(cats_string.e5);
				break;
			case "6":
				subcat.setInnerFBML(cats_string.e6);
				break;
			case "7":
				subcat.setInnerFBML(cats_string.e7);
				break;
			case "8":
				subcat.setInnerFBML(cats_string.e8);
				break;
		}
	}

<?php if(0) { ?>
</script>
	<?php } ?>
