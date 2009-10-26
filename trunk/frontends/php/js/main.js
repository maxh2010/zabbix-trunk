//Javascript document
/*
** ZABBIX
** Copyright (C) 2000-2009 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/ 

/************************************************************************************/
/*								PAGE REFRESH										*/
/************************************************************************************/
// Author: Aly
var PageRefresh = {
delay:			null,	// refresh timeout
delayLeft:		null,	// left till refresh
timeout:		null,	// link to timeout

init: function(time){
	this.delay = time;
	this.delayLeft = this.delay;
	this.start();
},

check: function(){
	if(is_null(this.delay)) return false;

	this.delayLeft -= 1000;
	if(this.delayLeft < 0)
		location.reload();
	else
		this.timeout = setTimeout('PageRefresh.check()', 1000);
},

start: function(){
	if(is_null(this.delay)) return false;
	
	this.timeout = setTimeout('PageRefresh.check()', 1000);
},

stop: function(){
	clearTimeout(this.timeout);	
}
}

/************************************************************************************/
/*								MAIN MENU stuff										*/
/************************************************************************************/
// Author: Aly

var MMenu = {
menus:			{'empty': 0, 'view': 0, 'cm': 0, 'reports': 0, 'config': 0, 'admin': 0},
def_label:		null,
sub_active: 	false,
timeout_reset:	null,
timeout_change:	null,

mouseOver: function(show_label){
	clearTimeout(this.timeout_reset);
	this.timeout_change = setTimeout('MMenu.showSubMenu("'+show_label+'")', 200);
	PageRefresh.stop();
},

submenu_mouseOver: function(){
	clearTimeout(this.timeout_reset);
	clearTimeout(this.timeout_change);
	PageRefresh.stop();
},

mouseOut: function(){
	clearTimeout(this.timeout_change);
	this.timeout_reset = setTimeout('MMenu.showSubMenu("'+this.def_label+'")', 2500);
	PageRefresh.start();
},

showSubMenu: function(show_label){
	var menu_div  = $('sub_'+show_label);
	if(!is_null(menu_div)){
		$(show_label).className = 'active';
		menu_div.show();
		for(var key in this.menus){
			if(key == show_label) continue;

			var menu_cell = $(key);
			if(!is_null(menu_cell)) menu_cell.className = '';

			var sub_menu_cell = $('sub_'+key);
			if(!is_null(sub_menu_cell)) sub_menu_cell.hide();
		}
	}
}
}

/************************************************************************************/
/*						Automatic checkbox range selection 							*/
/************************************************************************************/
// Author: Aly

var chkbxRange = {
startbox:			null,			// start checkbox obj
startbox_name: 		null,			// start checkbox name
chkboxes:			new Array(),	// ckbx list
pageGoName:			null,			// wich checkboxes should be counted by Go button
pageGoCount:		0,				// selected checkboxes
selected_ids:		{},	// ids of selected checkboxes

page:				null,			// loaded apge name

init: function(){
	var path = new Curl();
	this.page = path.getPath();
	
	this.selected_ids = cookie.readJSON('cb_'+this.page);

	var chk_bx = document.getElementsByTagName('input');

	for(var i=0; i < chk_bx.length; i++){
		if((typeof(chk_bx[i]) != 'undefined') && (chk_bx[i].type.toLowerCase() == 'checkbox')){
			this.implement(chk_bx[i]);
		}
	}
	
	var goButton = $('goButton');
	if(!is_null(goButton))
		addListener(goButton, 'click', this.submitGo.bindAsEventListener(this), false);
		
	this.setGo();
},

implement: function(obj){
	var obj_name = obj.name.split('[')[0];

	if(typeof(this.chkboxes[obj_name]) == 'undefined') this.chkboxes[obj_name] = new Array();
	this.chkboxes[obj_name].push(obj);

	addListener(obj, 'click', this.check.bindAsEventListener(this), false);
	
	if(obj_name == this.pageGoName){
		var obj_id  = obj.name.split('[')[1];
		obj_id = obj_id.substring(0, obj_id.lastIndexOf(']'));
		
		if(isset(obj_id, this.selected_ids)){
//SDI(obj_id);
			obj.checked = true;
		}
	}
},

check: function(e){
	var e = e || window.event;
	var obj = eventTarget(e);

	PageRefresh.stop();
	
	if((typeof(obj) == 'undefined') || (obj.type.toLowerCase() != 'checkbox')){
		return true;
	}

	this.setGo();

	if(!(e.ctrlKey || e.shiftKey)) return true;

	var obj_name = obj.name.split('[')[0];

	if(!is_null(this.startbox) && (this.startbox_name == obj_name) && (obj.name != this.startbox.name)){
		var chkbx_list = this.chkboxes[obj_name];
		var flag = false;

		for(var i=0; i < chkbx_list.length; i++){
			if(typeof(chkbx_list[i]) !='undefined'){
//alert(obj.name+' == '+chkbx_list[i].name);
				if(flag){
					chkbx_list[i].checked = this.startbox.checked;
				}

				if(obj.name == chkbx_list[i].name) break;
				if(this.startbox.name == chkbx_list[i].name) flag = true;
			}
		}

		if(flag){
			this.startbox = null;
			this.startbox_name = null;

			this.setGo();
			return true;
		}
		else{
			for(var i=chkbx_list.length-1; i >= 0; i--){
				if(typeof(chkbx_list[i]) !='undefined'){
//alert(obj.name+' == '+chkbx_list[i].name);
					if(flag){
						chkbx_list[i].checked = this.startbox.checked;
					}

					if(obj.name == chkbx_list[i].name){
						this.startbox = null;
						this.startbox_name = null;

						this.setGo();
						return true;
					}

					if(this.startbox.name == chkbx_list[i].name) flag = true;
				}
			}
		}

	}
	else{
		if(!is_null(this.startbox)) this.startbox.checked = !this.startbox.checked;

		this.startbox = obj;
		this.startbox_name = obj_name;
	}

	this.setGo();
},

checkAll: function(name, value){
	if(typeof(this.chkboxes[name]) == 'undefined') return false;

	var chk_bx = this.chkboxes[name];
	for(var i=0; i < chk_bx.length; i++){
		if((typeof(chk_bx[i]) !='undefined') && (chk_bx[i].disabled != true)){
			var box = chk_bx[i];
			var obj_name = chk_bx[i].name.split('[')[0];

			if(obj_name == name){
				chk_bx[i].checked = value;
			}

		}
	}
},

setGo: function(){
	if(!is_null(this.pageGoName)){
	
		if(typeof(this.chkboxes[this.pageGoName]) == 'undefined'){
//			alert('CheckBoxes with name '+this.pageGoName+' doesn\'t exist');
			return false;
		}

		var chk_bx = this.chkboxes[this.pageGoName];
		for(var i=0; i < chk_bx.length; i++){
			if(typeof(chk_bx[i]) !='undefined'){
				var box = chk_bx[i];
				
				var obj_name = box.name.split('[')[0];
				var obj_id  = box.name.split('[')[1];
				obj_id = obj_id.substring(0, obj_id.lastIndexOf(']'));
				
				var crow = getParent(box,'tr');

				if(box.checked){
					if(!is_null(crow)){
						var origClass = crow.getAttribute('origClass');
						if(is_null(origClass))
							crow.setAttribute('origClass',crow.className);
							
						crow.className = 'selected';
					}

					if(obj_name == this.pageGoName){
						this.selected_ids[obj_id] = obj_id;
					}
				}
				else{
					if(!is_null(crow)){
						var origClass = crow.getAttribute('origClass');

						if(!is_null(origClass)){
							crow.className = origClass;
							crow.removeAttribute('origClass');
						}
					}
					
					if(obj_name == this.pageGoName){
						delete(this.selected_ids[obj_id]);
					}
				}

			}
		}

		var countChecked = 0;
		for(var key in this.selected_ids){
			if(!empty(this.selected_ids[key]))
				countChecked++;
		}
		
		var tmp_val = $('goButton').value.split(' ');
		$('goButton').value = tmp_val[0]+' ('+countChecked+')';
		cookie.createJSON('cb_'+this.page, this.selected_ids);

		this.pageGoCount = countChecked;
	}
	else{
//		alert('Not isset pageGoName')
	}
},

submitGo: function(e){
	var e = e || window.event;

	if(this.pageGoCount > 0){
		var goButton = $('goButton');
		
		var goSelect = $('go');
		var confirmText = goSelect.options[goSelect.selectedIndex].getAttribute('confirm');
		if(is_null(confirmText) || !confirmText){
//			confirmText = 'Continue with "'+goSelect.options[goSelect.selectedIndex].text+'"?';
		}
		
		if(!Confirm(confirmText)){ 
			Event.stop(e);
			return false;
		}
		
		var form = getParent(goButton, 'form');
		for(var key in this.selected_ids){
			if(!empty(this.selected_ids[key]))
				create_var(form.name, this.pageGoName+'['+key+']', key, false);
		}

		return true;
	}
	else{
		alert('No elements selected!');
		Event.stop(e);
		return false;
	}
}
}


/************************************************************************************/
/*						Replace Standart Blink functionality						*/
/************************************************************************************/
// Author: Aly
var blink = {
	blinkobjs: new Array(),

	init: function(){
		this.blinkobjs = document.getElementsByName("blink");
		if(this.blinkobjs.length > 0) this.view();
	},
	hide: function(){
		for(var id=0; id<this.blinkobjs.length; id++){
			this.blinkobjs[id].style.visibility = 'hidden';
		}
		setTimeout('blink.view()',500);
	},
	view: function(){
		for(var id=0; id<this.blinkobjs.length; id++){
			this.blinkobjs[id].style.visibility = 'visible'
		}
		setTimeout('blink.hide()',1000);
	}
}


/************************************************************************************/
/*								ZABBIX HintBoxes 									*/
/************************************************************************************/
var hintBox = {
boxes:				{},				// array of dom Hint Boxes
boxesCount: 		0,				// unique box id


debug_status: 		0,				// debug status: 0 - off, 1 - on, 2 - SDI;
debug_info: 		'',				// debug string
debug_prev:			'',				// don't log repeated fnc

createBox: function(obj, hint_text, width, className, byClick){
	this.debug('createBox');
	
	var boxid = 'hintbox_'+this.boxesCount;
	
	var box = document.createElement('div');
	
	var obj_tag = obj.nodeName.toLowerCase();

	if((obj_tag == 'td') || (obj_tag == 'div') || (obj_tag == 'body')) obj.appendChild(box);
	else obj.parentNode.appendChild(box);
	
	box.setAttribute('id', boxid);
	box.style.visibility = 'hidden';
	box.className = 'hintbox';
	
	if(!empty(className)){
		hint_text = "<span class=" + className + ">" + hint_text + "</"+"span>";
	}
	
	if(!empty(width)){
		box.style.width = width+'px';
	}
	
	var close_link = '';
	if(byClick){
		close_link = '<div class="link" '+
						'style="text-align: right; backgground-color: #AAA; border-bottom: 1px #333 solid;" '+
						'onclick="javascript: hintBox.hide(event, \''+boxid+'\');">Close</div>';
	}

	box.innerHTML = close_link + hint_text;
	
/*	
	var box_close = document.createElement('div');
	box.appendChild(box_close);	
	box_close.appendChild(document.createTextNode('X'));
	box_close.className = 'link';
	box_close.setAttribute('style','text-align: right; backgground-color: #AAA;');
	box_close.onclick = eval("function(){ hintBox.hide('"+boxid+"'); }");
*/
	this.boxes[boxid] = box;
	this.boxesCount++;
	
return box;
},

showOver: function(e, obj, hint_text, width, className){
	this.debug('showOver');
	
	if (!e) var e = window.event;	
	
	var hintid = obj.getAttribute('hintid');
	var hintbox = $(hintid);

	if(!empty(hintbox)) 
		var byClick = hintbox.getAttribute('byclick');
	else
		var byClick = null;

	if(!empty(byClick)) return;

	var hintbox = this.createBox(obj,hint_text, width, className, false);
	
	obj.setAttribute('hintid', hintbox.id);
	this.show(e, obj, hintbox);
},

hideOut: function(e, obj){
	this.debug('hideOut');
	
	if (!e) var e = window.event;	
	
	var hintid = obj.getAttribute('hintid');
	var hintbox = $(hintid);

	if(!empty(hintbox)) 
		var byClick = hintbox.getAttribute('byclick');
	else
		var byClick = null;

	if(!empty(byClick)) return;
	
	if(!empty(hintid)){
		obj.removeAttribute('hintid');
		obj.removeAttribute('byclick');
	
		this.hide(e, hintid);
	}
},

onClick: function(e, obj, hint_text, width, className){
	this.debug('onClick');

	if (!e) var e = window.event;
	cancelEvent(e);
	
	var hintid = obj.getAttribute('hintid');
	var hintbox = $(hintid);

	if(!empty(hintbox)) 
		var byClick = hintbox.getAttribute('byclick');
	else
		var byClick = null;
	
	if(!empty(hintid) && empty(byClick)){
		obj.removeAttribute('hintid');
		this.hide(e, hintid);
		
		var hintbox = this.createBox(obj, hint_text, width, className, true);
		
		hintbox.setAttribute('byclick', 'true');
		obj.setAttribute('hintid', hintbox.id);
		
		this.show(e, obj, hintbox);
	}
	else if(!empty(hintid)){
		obj.removeAttribute('hintid');
		hintbox.removeAttribute('byclick');
		
		this.hide(e, hintid);
	}
	else{
		var hintbox = this.createBox(obj,hint_text, width, className, true);
		
		hintbox.setAttribute('byclick', 'true');
		obj.setAttribute('hintid', hintbox.id);
		
		this.show(e, obj, hintbox);
	}
},

show: function(e, obj, hintbox){
	this.debug('show');
	
	var hintid = hintbox.id;
	var body_width = get_bodywidth();
	
//	pos = getPosition(obj);
// this.debug('body width: ' + body_width);
// this.debug('position.top: ' + pos.top);

// by Object
/*
	if(parseInt(pos.left+obj.offsetWidth+4+hintbox.offsetWidth) > body_width){
		pos.left-=parseInt(hintbox.offsetWidth);
		pos.left-=4;
		pos.left=(pos.left < 0)?0:pos.left;
	}
	else{
		pos.left+= obj.offsetWidth+4;
	}
	hintbox.x	= pos.left;
//*/
	
	posit = $(obj).positionedOffset();
	cumoff = $(obj).cumulativeOffset();
	if(parseInt(cumoff.left+10+hintbox.offsetWidth) > body_width){
		posit.left-=parseInt(hintbox.offsetWidth);
		posit.left-=10;
		//posit.left=(pos.left < 0)?0:posit.left;
	}
	else{
		posit.left+=10;
	}	
	hintbox.x	= posit.left;
	hintbox.y	= posit.top;
	hintbox.style.left = hintbox.x + 'px';
	hintbox.style.top	= hintbox.y + 10 + parseInt(obj.offsetHeight/2) + 'px';
	hintbox.style.visibility = 'visible';
	hintbox.style.zIndex = '999';
	
// IE6 z-index bug
	//showPopupDiv(hintid, 'frame_'+hintid);
	
},

hide: function(e, boxid){
	this.debug('hide');
	
	if (!e) var e = window.event;	
	cancelEvent(e);

	var hint = $(boxid);
	if(!is_null(hint)){
		delete(this.boxes[boxid]);
		
		//hidePopupDiv('frame_'+hint.id);
// Opera refresh bug!
		hint.style.display = 'none';
		//hintbox.setAttribute('byclick', 'true');
		if(OP) setTimeout(function(){hint.remove();}, 200);
		else hint.remove();
		
	}
},

hideAll: function(){
	this.debug('hideAll');

	for(var id in this.boxes){
		if((typeof(this.boxes[id]) != 'undefined') && !empty(this.boxes[id])){
			this.hide(id);
		}
	}
},
	
debug: function(fnc_name, id){
	if(this.debug_status){
		var str = 'PMaster.'+fnc_name;
		if(typeof(id) != 'undefined') str+= ' :'+id;

		if(this.debug_prev == str) return true;

		this.debug_info += str + '\n';
		if(this.debug_status == 2){
			SDI(str);
		}
		
		this.debug_prev = str;
	}
}
}


/************************************************************************************/
/*								GRAPH RELATED STUFF 								*/
/************************************************************************************/
var graphs = {
graphtype : 0,
	
submit : function(obj){
	if(obj.name == 'graphtype'){
		if(((obj.selectedIndex > 1) && (this.graphtype < 2)) || ((obj.selectedIndex < 2) && (this.graphtype > 1))){
			var refr = document.getElementsByName('form_refresh');
			refr[0].value = 0;
		} 
	}
	document.getElementsByName('frm_graph')[0].submit();
}
}