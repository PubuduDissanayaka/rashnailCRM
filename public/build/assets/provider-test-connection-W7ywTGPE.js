import{S as r}from"./sweetalert2.esm.all-O8mKH-HY.js";document.addEventListener("DOMContentLoaded",function(){l(),v()});function l(){document.querySelectorAll(".test-provider-btn").forEach(o=>{o.addEventListener("click",function(n){n.preventDefault();const e=this.getAttribute("data-provider-id");c(e)})});const t=document.getElementById("test-connection-btn");t&&t.addEventListener("click",function(o){o.preventDefault(),d()})}function c(t){const o=document.querySelector(`.test-provider-btn[data-provider-id="${t}"]`),n=o.innerHTML;o.disabled=!0,o.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Testing...',fetch(`/notification-providers/${t}/test`,{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content"),Accept:"application/json"}}).then(e=>{if(!e.ok)throw new Error(`HTTP error! status: ${e.status}`);return e.json()}).then(e=>{if(e.success)r.fire({title:"Success!",text:e.message,icon:"success",customClass:{confirmButton:"btn btn-primary"}}),m(t,e.status);else throw new Error(e.message||"Connection test failed")}).catch(e=>{console.error("Error testing provider connection:",e),r.fire({title:"Error!",text:e.message||"Failed to test provider connection",icon:"error",customClass:{confirmButton:"btn btn-primary"}})}).finally(()=>{o.disabled=!1,o.innerHTML=n})}function d(){const t=document.getElementById("provider-form"),o=document.getElementById("test-connection-btn");if(!t||!o)return;const n=o.innerHTML;if(!f())return;o.disabled=!0,o.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Testing...';const e=new FormData(t);e.append("test_only","true"),fetch(t.action,{method:"POST",body:e,headers:{"X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content"),Accept:"application/json"}}).then(i=>{if(!i.ok)throw new Error(`HTTP error! status: ${i.status}`);return i.json()}).then(i=>{if(i.success)r.fire({title:"Success!",text:i.message,icon:"success",customClass:{confirmButton:"btn btn-primary"}}),i.recommendations&&u(i.recommendations);else throw new Error(i.message||"Connection test failed")}).catch(i=>{console.error("Error testing provider connection:",i),r.fire({title:"Error!",text:i.message||"Failed to test provider connection",icon:"error",customClass:{confirmButton:"btn btn-primary"}}),i.details&&p(i.details)}).finally(()=>{o.disabled=!1,o.innerHTML=n})}function f(){const t=document.getElementById("provider-form"),o=t.querySelector("#channel").value,n=t.querySelector("#provider").value;let e=!0,i="";switch(o){case"email":switch(n){case"smtp":t.querySelector("#config_host").value||(e=!1,i="SMTP host is required"),t.querySelector("#config_port").value||(e=!1,i="SMTP port is required");break;case"mailgun":t.querySelector("#config_domain").value||(e=!1,i="Mailgun domain is required"),t.querySelector("#config_secret").value||(e=!1,i="Mailgun secret is required");break;case"sendgrid":t.querySelector("#config_api_key").value||(e=!1,i="SendGrid API key is required");break;case"ses":t.querySelector("#config_key").value||(e=!1,i="AWS key is required"),t.querySelector("#config_secret").value||(e=!1,i="AWS secret is required");break}break;case"sms":t.querySelector("#config_api_key").value||(e=!1,i="API key is required for SMS provider");break;case"push":t.querySelector("#config_api_key").value||(e=!1,i="API key is required for push provider");break}return e||r.fire({title:"Validation Error",text:i,icon:"warning",customClass:{confirmButton:"btn btn-primary"}}),e}function m(t,o){const n=document.querySelector(`.provider-status[data-provider-id="${t}"]`);if(n){let i="badge ",s="";switch(o){case"active":i+="bg-success",s="Active";break;case"inactive":i+="bg-secondary",s="Inactive";break;case"testing":i+="bg-warning",s="Testing";break;case"failed":i+="bg-danger",s="Failed";break;default:i+="bg-info",s="Unknown"}n.className=i,n.textContent=s}const e=document.querySelector(`.last-test[data-provider-id="${t}"]`);e&&(e.textContent="Just now")}function u(t){let o='<ul class="text-start">';t.forEach(n=>{o+=`<li>${n}</li>`}),o+="</ul>",r.fire({title:"Test Recommendations",html:o,icon:"info",customClass:{confirmButton:"btn btn-primary"}})}function p(t){r.fire({title:"Detailed Error Information",html:`<pre class="text-start">${JSON.stringify(t,null,2)}</pre>`,icon:"error",customClass:{confirmButton:"btn btn-primary"},width:"600px"})}function v(){const t=document.getElementById("channel"),o=document.getElementById("provider");t&&o&&(t.addEventListener("change",function(){b(this.value),a(this.value,o.value)}),o.addEventListener("change",function(){a(t.value,this.value)}),a(t.value,o.value))}function b(t){const o=document.getElementById("provider");if(!o)return;o.innerHTML="";let n=[];switch(t){case"email":n=[{value:"smtp",text:"SMTP"},{value:"mailgun",text:"Mailgun"},{value:"sendgrid",text:"SendGrid"},{value:"ses",text:"Amazon SES"}];break;case"sms":n=[{value:"twilio",text:"Twilio"},{value:"nexmo",text:"Nexmo/Vonage"},{value:"plivo",text:"Plivo"}];break;case"in_app":n=[{value:"system",text:"System"}];break;case"push":n=[{value:"fcm",text:"Firebase Cloud Messaging"},{value:"apns",text:"Apple Push Notification Service"}];break;default:n=[{value:"",text:"Select provider type"}]}n.forEach(e=>{const i=document.createElement("option");i.value=e.value,i.textContent=e.text,o.appendChild(i)})}function a(t,o){const n=document.getElementById("config-fields-container");if(!n)return;let e="";switch(t){case"email":switch(o){case"smtp":e=`
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_host" class="form-label">SMTP Host *</label>
                                    <input type="text" class="form-control" id="config_host" name="config[host]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_port" class="form-label">SMTP Port *</label>
                                    <input type="number" class="form-control" id="config_port" name="config[port]" value="587" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="config_username" name="config[username]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="config_password" name="config[password]">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="config_encryption" name="config[encryption]">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="">None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_from_address" class="form-label">From Address *</label>
                                    <input type="email" class="form-control" id="config_from_address" name="config[from_address]" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="config_from_name" name="config[from_name]">
                        </div>
                    `;break;case"mailgun":e=`
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_domain" class="form-label">Domain *</label>
                                    <input type="text" class="form-control" id="config_domain" name="config[domain]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_secret" class="form-label">Secret Key *</label>
                                    <input type="password" class="form-control" id="config_secret" name="config[secret]" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_endpoint" class="form-label">Endpoint</label>
                                    <select class="form-select" id="config_endpoint" name="config[endpoint]">
                                        <option value="api.mailgun.net">US (api.mailgun.net)</option>
                                        <option value="api.eu.mailgun.net">EU (api.eu.mailgun.net)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_from_address" class="form-label">From Address *</label>
                                    <input type="email" class="form-control" id="config_from_address" name="config[from_address]" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="config_from_name" name="config[from_name]">
                        </div>
                    `;break;default:e='<div class="alert alert-info">Select a provider type to see configuration options.</div>'}break;case"sms":e=`
                <div class="mb-3">
                    <label for="config_api_key" class="form-label">API Key *</label>
                    <input type="password" class="form-control" id="config_api_key" name="config[api_key]" required>
                </div>
                <div class="mb-3">
                    <label for="config_api_secret" class="form-label">API Secret</label>
                    <input type="password" class="form-control" id="config_api_secret" name="config[api_secret]">
                </div>
                <div class="mb-3">
                    <label for="config_from_number" class="form-label">From Number *</label>
                    <input type="text" class="form-control" id="config_from_number" name="config[from_number]" required>
                </div>
                <div class="mb-3">
                    <label for="config_account_sid" class="form-label">Account SID (Twilio)</label>
                    <input type="text" class="form-control" id="config_account_sid" name="config[account_sid]">
                </div>
            `;break;case"in_app":e='<div class="alert alert-info">In-app notifications require no additional configuration.</div>';break;case"push":e=`
                <div class="mb-3">
                    <label for="config_api_key" class="form-label">API Key *</label>
                    <input type="password" class="form-control" id="config_api_key" name="config[api_key]" required>
                </div>
                <div class="mb-3">
                    <label for="config_project_id" class="form-label">Project ID (FCM)</label>
                    <input type="text" class="form-control" id="config_project_id" name="config[project_id]">
                </div>
                <div class="mb-3">
                    <label for="config_bundle_id" class="form-label">Bundle ID (APNS)</label>
                    <input type="text" class="form-control" id="config_bundle_id" name="config[bundle_id]">
                </div>
                <div class="mb-3">
                    <label for="config_certificate" class="form-label">Certificate (APNS)</label>
                    <textarea class="form-control" id="config_certificate" name="config[certificate]" rows="4"></textarea>
                </div>
            `;break;default:e='<div class="alert alert-info">Select a channel to see configuration options.</div>'}n.innerHTML=e}
