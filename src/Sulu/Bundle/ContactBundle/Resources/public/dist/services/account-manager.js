define(["services/husky/util","services/husky/mediator","sulucontact/models/account","sulucontact/models/contact","sulucontact/models/accountContact","sulucontact/models/email","sulucontact/models/emailType","sulumedia/model/media","sulucategory/model/category","sulucategory/model/category"],function(a,b,c,d,e,f,g,h,i){"use strict";function j(){}var k=null,l=function(b,c){var d=[],e=$.Deferred();return b.length?(a.each(b,function(b,e){d.push(a.ajax({url:"/admin/api/accounts/"+c+"/medias/"+e,data:{mediaId:e},type:"DELETE"}))}.bind(this)),$.when.apply(null,d).then(function(){e.resolve()}.bind(this))):e.resolve(),e},m=function(b,c){var d=[],e=$.Deferred();return b.length?(a.each(b,function(b,e){d.push(a.ajax({url:"/admin/api/accounts/"+c+"/medias",data:{mediaId:e},type:"POST"}))}.bind(this)),$.when.apply(null,d).then(function(){e.resolve()}.bind(this))):e.resolve(),e};return j.prototype={loadOrNew:function(a){var b,d=$.Deferred();return a?(b=c.findOrCreate({id:a}),b.fetch({success:function(){d.resolve(b.toJSON())}.bind(this),error:function(){d.fail()}.bind(this)})):(b=new c,d.resolve(b.toJSON())),d},"delete":function(a,b){var d=$.Deferred(),e=c.findOrCreate({id:a});return e.destroy({data:{removeContacts:!!b},processData:!0,success:function(){d.resolve()}.bind(this),error:function(){d.fail()}.bind(this)}),d},save:function(b){var d=$.Deferred(),e=b.id?c.findOrCreate({id:b.id}):new c;return e.set(b),e.get("categories").reset(),a.foreach(b.categories,function(a){var b=i.findOrCreate({id:a});e.get("categories").add(b)}.bind(this)),e.save(null,{success:function(a){d.resolve(a.toJSON())}.bind(this),error:function(){d.fail()}.bind(this)}),d},removeAccountContacts:function(f,g){var h=c.findOrCreate({id:f});b.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","sulu.overlay.delete-desc",null,function(){var c;a.foreach(g,function(a){c=e.findOrCreate({id:f,contact:d.findOrCreate({id:a}),account:h}),c.destroy({success:function(){b.emit("sulu.contacts.accounts.contacts.removed",a)}.bind(this)})}.bind(this))}.bind(this))},addAccountContact:function(a,b,f){var g=$.Deferred(),h=c.findOrCreate({id:a}),i=e.findOrCreate({id:b,contact:d.findOrCreate({id:b}),account:h});return f&&i.set({position:f}),i.save(null,{success:function(a){var b=a.toJSON();g.resolve(b)}.bind(this),error:function(){g.fail()}.bind(this)}),g},setMainContact:function(a,b){var e=$.Deferred(),f=c.findOrCreate({id:a});return f.set({mainContact:d.findOrCreate({id:b})}),f.save(null,{patch:!0,success:function(){e.resolve()}.bind(this)}),e},saveDocuments:function(a,b,c){var d=$.Deferred(),e=m.call(this,b,a),f=l.call(this,c,a);return $.when(e,f).then(function(){d.resolve()}.bind(this)),d},loadDeleteInfo:function(b){var c=$.Deferred();return a.ajax({headers:{"Content-Type":"application/json"},type:"GET",url:"/admin/api/accounts/"+b+"/deleteinfo",success:function(a){c.resolve(a)}.bind(this)}),c},loadMultipleDeleteInfo:function(b){var c=$.Deferred();return a.ajax({headers:{"Content-Type":"application/json"},type:"GET",url:"/admin/api/accounts/multipledeleteinfo",data:{ids:b},success:function(a){c.resolve(a)}.bind(this)}),c}},j.getInstance=function(){return null==k&&(k=new j),k},j.getInstance()});