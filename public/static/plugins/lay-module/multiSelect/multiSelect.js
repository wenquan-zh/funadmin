'use strict';layui.define(['jquery','dropdown'],function(exports){"use strict";var $=layui.jquery;var laytpl=layui.laytpl;var dropdown=layui.dropdown;var MOD_NAME='multiSelect';var select={config:{valueSeparator:',',keywordPlaceholder:'请输入关键词',unfilteredText:'没有匹配的选项',customName:{id:'id',title:'title',selected:'selected'},options:[],allowCreate:true,collapseSelected:false,url:undefined,parseOptions:undefined}};var thisSelect=function thisSelect(){var that=this;return{config:that.config,reload:function reload(config){that.reload.call(that,config);},val:function val(value){if(value===undefined){return that.context.selectedIds.join(that.config.valueSeparator);}that.clear();var ids=$.isArray(value)?value:value.split(that.config.valueSeparator);for(var i=0;i<ids.length;i++){var option=that.getOptionById(ids[i]);if(!option){continue;}that.select(option);}},clear:function clear(){that.clear.call(that);}};};var Class=function Class(config){var that=this;that.config=$.extend({},that.config,select.config,config);that.config.elem=$(config.elem);that.config.elem.css({position:'absolute',color:'#FFF',userSelect:'none',top:0,height:'100%'});var width=that.config.elem.parent().outerWidth();var layuiInputBlock=that.config.elem.parents('.layui-input-block');if(layuiInputBlock.length>0){width=layuiInputBlock.width();}var layuiInputGroup=that.config.elem.parents('.layui-input-group');if(layuiInputGroup.length>0){width-=layuiInputGroup.width();}that.config.elem.wrap('<div class="layui-form-select multiple-select" style="width:'+width+'px"></div>');that.config.elem.after('<div class="layui-input-suffix"><i class="layui-edge"></i></div>');that.render();};Class.prototype.render=function(){var that=this;if(that.config.url){$.get(that.config.url,{},function(res){var options=[];if(that.config.parseOptions){options=that.config.parseOptions(res);}else{options=res;}console.log(options);that.doRender(options);});return;}that.doRender(that.config.options);};Class.prototype.doRender=function(options){var that=this;var initOptions=options.map(function(option){return{id:option[that.config.customName.id],title:option[that.config.customName.title],selected:option[that.config.customName.selected]};});var selectedIds=initOptions.filter(function(option){return option.selected;}).map(function(option){return option.id;});that.config.elem.val(selectedIds.join(that.config.valueSeparator));that.config.elem.parent().on('click','.multiple-select-selection-item-remove',function(e){e.stopPropagation();var id=$(this).parent().data('id');var option=that.getOptionById(id);that.remove(option);});that.context={keyword:'',filteredOptions:null,selectedIds:selectedIds,options:initOptions,dropdownMenuScrollTop:0};that.renderDropdown();that.renderSelection();};Class.prototype.clear=function(){this.context.selectedIds=[];this.reloadDropdownData(this.buildRenderOptions());};Class.prototype.remove=function(option){this.context.selectedIds=this.context.selectedIds.filter(function(id){return id!=option.id;});this.reloadDropdownData(this.buildRenderOptions());};Class.prototype.select=function(options){options=layui.isArray(options)?options:[options];var length=options.length;for(var i=0;i<length;i++){var id=options[i].id;if(this.context.selectedIds.indexOf(id)!==-1){continue;}this.context.selectedIds.push(id)}this.reloadDropdownData(this.buildRenderOptions());};Class.prototype.resetSearch=function(){this.context.filteredOptions=null;this.context.keyword='';var input=this.getSearchInput();input&&input.val('');};Class.prototype.reload=function(config){if(config){this.config=$.extend({},this.config,config||{});}this.render();};Class.prototype.buildRenderOptions=function(options){var that=this,renderOptions=options||[];if(renderOptions.length===0){if(that.context.filteredOptions!==null){renderOptions=that.context.filteredOptions;}else{renderOptions=this.getAllOptions();}}return renderOptions.map(function(option){option.selected=that.context.selectedIds.indexOf(option.id)!==-1;return option;});};Class.prototype.filterOptions=function(keyword){var that=this,keyword=keyword.toLowerCase();return this.getAllOptions().filter(function(item){return-1!==item.title.toLowerCase().indexOf(keyword);});};Class.prototype.getAllOptions=function(){var that=this;return that.context.options.map(function(item){return{id:item.id,title:item.title,selected:that.context.selectedIds.indexOf(item.id)!==-1};});};Class.prototype.buildDropdownContent=function(options){return['<div class="multiple-select-search"><input class="layui-input" placeholder="'+this.config.keywordPlaceholder+'"/></div>','<ul class="layui-menu layui-dropdown-menu" style="max-height: 300px;overflow-y: auto;">',options.length>0?options.map(function(option){return'<li data-value="'+option.id+'" class="multiple-select-option '+(option.selected?'multiple-select-option-selected':'')+'">'+option.title+'</li>';}).join(''):'<div style="padding: 5px;font-size:12px;">'+this.config.unfilteredText+'</div>','</ul>'].join('');};Class.prototype.reloadDropdownData=function(options){if(options&&options.length===this.getAllOptions().length){this.resetSearch();}var renderOptions=this.buildRenderOptions(options);this.dropdown.reloadData({content:this.buildDropdownContent(renderOptions)});this.renderSelection();};Class.prototype.getSearchInput=function(){if(!this.panel){return;}return this.panel.find('div.multiple-select-search > input');};Class.prototype.renderDropdown=function(){var that=this;that.dropdown=dropdown.render({elem:that.config.elem.parent(),style:'width:'+that.config.elem.outerWidth()+'px',content:that.buildDropdownContent(that.getAllOptions()),ready:function ready(panel,elem){that.panel=panel;elem.addClass('multiple-select-panel-opended');if(that.context.dropdownMenuScrollTop>0){panel.find('.layui-dropdown-menu').scrollTop(that.context.dropdownMenuScrollTop);}var inputCompositionStart=false,searchInput=that.getSearchInput();function reloadDropdown(keyword){var filterOptions=that.filterOptions(keyword);if(filterOptions.length===0&&that.config.allowCreate){filterOptions.push({id:keyword,title:keyword});}that.context.keyword=keyword;that.context.filteredOptions=filterOptions;that.reloadDropdownData(that.context.filteredOptions);}searchInput.on('input',function(){if(inputCompositionStart){return;}reloadDropdown($(this).val());}).on('compositionstart',function(){inputCompositionStart=true;}).on('compositionend',function(){inputCompositionStart=false;reloadDropdown($(this).val());});if(that.context.keyword!==''){searchInput.val(that.context.keyword).focus();}},click:function click(data,elem){var value=elem.data('value'),option=that.getOptionById(value);if(!option){if(!that.config.allowCreate){return;}option={id:value,title:value,selected:true};that.context.options.push(option);}that.context.dropdownMenuScrollTop=elem.parent().scrollTop();if(that.context.selectedIds.indexOf(option.id)===-1){that.select(option);}else{that.remove(option);}return false;},close:function close(elem){that.context.keyword='';that.context.filteredOptions=null;that.reloadDropdownData();elem.removeClass('multiple-select-panel-opended');}});};Class.prototype.getOptionById=function(id){return this.getAllOptions().filter(function(option){return option.id==id;})[0];};Class.prototype.renderSelection=function(){var that=this,inputWrap=that.config.elem.parent(),selectedOptions=that.getSelectedOptions(),options=selectedOptions.length>1&&that.config.collapseSelected?[selectedOptions[0]]:selectedOptions,selectionHtml='',selectionTpl='\n          <div class="multiple-select-selection">\n            <div class="multiple-select-selection-overflow">\n              {{# layui.each(d.options, function(index, option) { }}\n                <div class="multiple-select-selection-overflow-item">\n                  <span class="multiple-select-selection-item" data-id="{{= option.id }}">\n                    <span class="multiple-select-selection-item-content">{{= option.title}}</span>\n                    <i class="layui-icon layui-icon-close multiple-select-selection-item-remove"></i>\n                  </span>\n                </div>\n              {{# }) }}\n              {{# if (d.collapseSelected && d.total > 1) { }}\n                <div class="multiple-select-selection-overflow-item">\n                  <span class="multiple-select-selection-item">\n                    <span class="multiple-select-selection-item-content">+ {{ d.total }}</span>\n                  </span>\n                </div>\n              {{# } }}\n            </div>\n          </div>\n        ';if(selectedOptions.length>0){selectionHtml=laytpl(selectionTpl).render({total:selectedOptions.length,options:options,collapseSelected:that.config.collapseSelected});}inputWrap.find('.multiple-select-selection').remove();selectionHtml!==''&&inputWrap.append(selectionHtml);this.config.elem.val(this.context.selectedIds.join(this.context.valueSeparator));};Class.prototype.getSelectedOptions=function(){var selectedIds=this.context.selectedIds;var selectedOptions=[];for(var i=0;i<selectedIds.length;i++){selectedOptions.push(this.getOptionById(selectedIds[i]));}return selectedOptions;};select.render=function(options){var inst=new Class(options);return thisSelect.call(inst);};layui.link('/static/plugins/lay-module/multiSelect/multiSelect.css');exports(MOD_NAME,select);});