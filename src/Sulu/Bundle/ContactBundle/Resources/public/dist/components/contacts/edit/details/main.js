define(["config","widget-groups","services/sulucontact/contact-manager"],function(a,b,c){"use strict";var d="#contact-form",e=["urls","emails","faxes","phones","notes"],f={tagsId:"#tags",addressAddId:"#address-add",addAddressWrapper:".grid-row",addressesSelector:"#addresses",bankAccountsId:"#bankAccounts",bankAccountAddSelector:".bank-account-add",editFormSelector:"#contact-edit-form"},g={addBankAccountsIcon:['<div class="grid-row">','    <div class="grid-col-12">','       <span id="bank-account-add" class="fa-plus-circle icon bank-account-add clickable pointer m-left-140"></span>',"   </div>","</div>"].join("")};return function(){return{view:!0,layout:function(){return{content:{width:"fixed"},sidebar:{width:"max",cssClasses:"sidebar-padding-50"}}},templates:["/admin/contact/template/contact/form"],customTemplates:{addAddressesIcon:['<div class="grid-row">','    <div class="grid-col-12">','       <span id="address-add" class="fa-plus-circle icon address-add clickable pointer m-left-140"></span>',"   </div>","</div>"].join("")},initialize:function(){this.saved=!0,this.formId="#contact-form",this.autoCompleteInstanceName="accounts-",this.dfdAllFieldsInitialized=this.sandbox.data.deferred(),this.dfdListenForChange=this.sandbox.data.deferred(),this.dfdFormIsSet=this.sandbox.data.deferred(),this.dfdBirthdayIsSet=this.sandbox.data.deferred(),this.sandbox.data.when(this.dfdListenForChange,this.dfdBirthdayIsSet).then(function(){this.dfdAllFieldsInitialized.resolve()}.bind(this)),c.loadOrNew(this.options.id).then(function(a){this.data=a,this.render(),this.listenForChange(),this.data&&this.data.id&&b.exists("contact-detail")&&this.initSidebar("/admin/widget-groups/contact-detail?contact=",this.data.id),this.setHeaderBar(!0)}.bind(this))},initSidebar:function(a,b){this.sandbox.emit("sulu.sidebar.set-widget",a+b)},render:function(){this.sandbox.once("sulu.contacts.set-defaults",this.setDefaults.bind(this)),this.sandbox.once("sulu.contacts.set-types",this.setTypes.bind(this)),this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/contact/template/contact/form")),this.sandbox.on("husky.dropdown.type.item.click",this.typeClick.bind(this));var a=this.initContactData();this.companyInstanceName="companyContact"+a.id,this.initForm(a),this.setTags(a),this.bindDomEvents(),this.bindCustomEvents(),this.bindTagEvents(a)},setTags:function(){var a=this.sandbox.util.uniqueId();this.data.id&&(a+="-"+this.data.id),this.autoCompleteInstanceName+=a,this.dfdFormIsSet.then(function(){this.sandbox.start([{name:"auto-complete-list@husky",options:{el:"#tags",instanceName:this.autoCompleteInstanceName,getParameter:"search",itemsKey:"tags",remoteUrl:"/admin/api/tags?flat=true&sortBy=name&searchFields=name",completeIcon:"tag",noNewTags:!0}}])}.bind(this))},bindTagEvents:function(a){a.tags&&a.tags.length>0?(this.sandbox.on("husky.auto-complete-list."+this.autoCompleteInstanceName+".initialized",function(){this.sandbox.emit("husky.auto-complete-list."+this.autoCompleteInstanceName+".set-tags",a.tags)}.bind(this)),this.sandbox.on("husky.auto-complete-list."+this.autoCompleteInstanceName+".items-added",function(){this.dfdListenForChange.resolve()}.bind(this))):this.dfdListenForChange.resolve()},setDefaults:function(a){this.defaultTypes=a},setTypes:function(a){this.fieldTypes=a},setFormData:function(a,b){this.numberOfBankAccounts=a.bankAccounts?a.bankAccounts.length:0,this.updateBankAccountAddIcon(this.numberOfBankAccounts),this.sandbox.emit("sulu.contact-form.add-collectionfilters",d),this.sandbox.form.setData(d,a).then(function(){b?this.sandbox.start(d):this.sandbox.start("#contact-fields"),this.sandbox.emit("sulu.contact-form.add-required",["email"]),this.sandbox.emit("sulu.contact-form.content-set"),this.dfdFormIsSet.resolve()}.bind(this)).fail(function(a){this.sandbox.logger.error("An error occured when setting data!",a)}.bind(this))},initForm:function(b){var c=a.get("sulucontact.components.autocomplete.default.account");c.el="#company",c.value=b.account?b.account:"",c.instanceName=this.companyInstanceName,this.sandbox.start([{name:"auto-complete@husky",options:c}]),this.numberOfAddresses=b.addresses.length,this.updateAddressesAddIcon(this.numberOfAddresses),this.sandbox.on("sulu.contact-form.initialized",function(){var a=this.sandbox.form.create(d);a.initialized.then(function(){this.setFormData(b,!0)}.bind(this))}.bind(this)),this.sandbox.start([{name:"contact-form@sulucontact",options:{el:f.editFormSelector,fieldTypes:this.fieldTypes,defaultTypes:this.defaultTypes}}])},updateAddressesAddIcon:function(a){var b,c=this.sandbox.dom.find(f.addressAddId);a&&a>0&&0===c.length?(b=this.sandbox.dom.createElement(this.customTemplates.addAddressesIcon),this.sandbox.dom.after(this.sandbox.dom.find("#addresses"),b)):0===a&&c.length>0&&(this.sandbox.dom.remove(this.sandbox.dom.closest(c,f.addAddressWrapper)),this.sandbox.emit("sulu.contact-form.update.addAddressLabel",f.addressesSelector))},bindDomEvents:function(){},bindCustomEvents:function(){this.sandbox.on("sulu.contact-form.added.address",function(){this.numberOfAddresses++,this.updateAddressesAddIcon(this.numberOfAddresses)},this),this.sandbox.on("sulu.contact-form.removed.address",function(){this.numberOfAddresses--,this.updateAddressesAddIcon(this.numberOfAddresses)},this),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("sulu.contacts.contact.delete",this.data.id)},this),this.sandbox.on("sulu.contacts.contacts.saved",function(a){this.data=a,this.initContactData(),this.setFormData(a),this.setHeaderBar(!0)},this),this.sandbox.on("sulu.toolbar.save",function(a){this.submit(a)},this),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.contacts.contacts.list")},this),this.sandbox.on("husky.input.birthday.initialized",function(){this.dfdBirthdayIsSet.resolve()},this),this.sandbox.once("husky.select.position-select.initialize",function(){this.sandbox.dom.find("#"+this.companyInstanceName).val()||this.enablePositionDropdown(!1)},this),this.sandbox.on("sulu.contact-form.added.bank-account",function(){this.numberOfBankAccounts++,this.updateBankAccountAddIcon(this.numberOfBankAccounts)},this),this.sandbox.on("sulu.contact-form.removed.bank-account",function(){this.numberOfBankAccounts--,this.updateBankAccountAddIcon(this.numberOfBankAccounts)},this),this.sandbox.on("sulu.router.navigate",this.cleanUp.bind(this))},cleanUp:function(){this.sandbox.stop(f.editFormSelector)},initContactData:function(){var a=this.data;return this.sandbox.util.foreach(e,function(b){a.hasOwnProperty(b)||(a[b]=[])}),a.emails=this.fillFields(a.emails,1,{id:null,email:"",emailType:this.defaultTypes.emailType}),a.phones=this.fillFields(a.phones,1,{id:null,phone:"",phoneType:this.defaultTypes.phoneType}),a.faxes=this.fillFields(a.faxes,1,{id:null,fax:"",faxType:this.defaultTypes.faxType}),a.notes=this.fillFields(a.notes,1,{id:null,value:""}),a.urls=this.fillFields(a.urls,0,{id:null,url:"",urlType:this.defaultTypes.urlType}),a},typeClick:function(a,b){this.sandbox.logger.log("email click",a),b.find("*.type-value").data("element").setValue(a)},fillFields:function(a,b,c){var d,e=-1,f=a.length;for(b>f&&(f=b);++e<f;)d=e+1>b?{}:{permanent:!0},a[e]?a[e].attributes=d:(a.push(c),a[a.length-1].attributes=d);return a},submit:function(a){if(this.sandbox.logger.log("save Model"),this.sandbox.form.validate(d)){var b=this.sandbox.form.getData(d);""===b.id&&delete b.id,b.tags=this.sandbox.dom.data(this.$find(f.tagsId),"tags"),b.account={id:this.sandbox.dom.attr("#"+this.companyInstanceName,"data-id")},this.sandbox.logger.log("log data",b),this.sandbox.emit("sulu.contacts.contacts.save",b,a)}},setHeaderBar:function(a){a!==this.saved&&(a?this.sandbox.emit("sulu.header.toolbar.item.disable","save",!0):this.sandbox.emit("sulu.header.toolbar.item.enable","save",!1)),this.saved=a},initializeDropDownListender:function(a){var b="husky.select."+a;this.sandbox.on(b+".selected.item",function(a){a>0&&this.setHeaderBar(!1)}.bind(this)),this.sandbox.on(b+".deselected.item",function(){this.setHeaderBar(!1)}.bind(this))},enablePositionDropdown:function(a){a?this.sandbox.emit("husky.select.position-select.enable"):this.sandbox.emit("husky.select.position-select.disable")},listenForChange:function(){this.sandbox.data.when(this.dfdAllFieldsInitialized).then(function(){this.sandbox.dom.on("#contact-form","change",function(){this.setHeaderBar(!1)}.bind(this),".changeListener select, .changeListener input, .changeListener textarea"),this.sandbox.dom.on("#contact-form","keyup",function(){this.setHeaderBar(!1)}.bind(this),".changeListener select, .changeListener input, .changeListener textarea"),this.sandbox.on("sulu.contact-form.changed",function(){this.setHeaderBar(!1)}.bind(this)),this.sandbox.dom.on("#company","keyup",function(a){a.target.value||this.enablePositionDropdown(!1)}.bind(this)),this.companySelected="husky.auto-complete."+this.companyInstanceName+".select",this.sandbox.on(this.companySelected,function(){this.enablePositionDropdown(!0)}.bind(this))}.bind(this)),this.sandbox.on("husky.select.form-of-address.selected.item",function(){this.setHeaderBar(!1)}.bind(this)),this.initializeDropDownListender("title-select","api/contact/titles"),this.initializeDropDownListender("position-select","api/contact/positions")},updateBankAccountAddIcon:function(a){var b,c=this.sandbox.dom.find(f.bankAccountAddSelector,this.$el);a&&a>0&&0===c.length?(b=this.sandbox.dom.createElement(g.addBankAccountsIcon),this.sandbox.dom.after(this.sandbox.dom.find(f.bankAccountsId),b)):0===a&&c.length>0&&this.sandbox.dom.remove(this.sandbox.dom.closest(c,f.addBankAccountsWrapper))}}}()});