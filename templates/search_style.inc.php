<script><!--

function clearSearchResults(doc) {
	this.searchresults = doc.getElementById('searchresults');
	
	this.searchresults.setTextValue('');
}

function do_ajax(pagetogo, divtowrite, divtohide) {
  document.getElementById('loader').setStyle('visibility', 'visible');
  var ajax = new Ajax();
  ajax.responseType = Ajax.FBML;
      ajax.ondone = function(data) {
        document.getElementById(divtowrite).setInnerFBML(data);
        document.getElementById(divtowrite).setStyle('display', 'block');
		document.getElementById('loader').setStyle('visibility', 'hidden');
		if (divtohide != null) {
			document.getElementById(divtohide).setStyle('display', 'none');
		}
      }
  ajax.requireLogin = true;
  ajax.post(pagetogo);
}
//--></script>

<style>
.growtogether_short {
float:left;
}

.ad:hover {
background-color: #e0e0ff;
}

.growtogether_short .ad {
float:left;
margin: 1px 1px 1px 2px;
padding: 1px 2px 1px 1px;
border-left: 1px solid #ccc;
}

.ad a {
text-decoration:none;
}


.lists th {
     text-align: left;
     padding: 5px 10px;
     background: #6d84b4;
}

.lists .spacer {
     background: none;
     border: none;
     padding: 0px;
     margin: 0px;
     width: 10px; 
}

.lists th h4 { float: left; color: white; }
.lists th h4 a { float: left; color: white; font-weight: bold; }
.lists th a { float: right; font-weight: normal; color: #d9dfea; }
.lists th a:hover { color: white; }

.lists td {
     margin:0px 10px;
     padding:0px;
     vertical-align:top;
}

.lists .list {
     background:white none repeat scroll 0%;
     border-color:-moz-use-text-color #BBBBBB;
     border-style:none solid;
     border-width:medium 1px;
}

.lists .image {
     width:60px;	
}

.lists .titleleft {
     width:400px;	
}

.lists .title {

}

.list_item { border-top:1px solid #E5E5E5; padding: 10px; }
.list_itemfirst { border-top: none;  padding: 10px; }

.lists .see_all {
     background:white none repeat scroll 0%;
     border-color:-moz-use-text-color #BBBBBB rgb(187, 187, 187);
     border-style:none solid solid;
     border-width:medium 1px 1px;
     text-align:left;
}

.lists {
		 width: 100%;
}

.lists .see_all div { border-top:1px solid #E5E5E5; padding:5px 10px; }

.titletable th { padding:5px 5px 0px 5px; }
.titletable { width: 100%;}

.inlineform {
	display: inline;
}

.rightaligned {
	text-align:right;
}

.result_pages {
	text-align: right;
	font-size: 16px;
	text-decoration: underline;
}

.already_shared {
	color: #ff0000;
}
</style>