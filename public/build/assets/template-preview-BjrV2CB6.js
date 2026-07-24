import{S as r}from"./sweetalert2.esm.all-O8mKH-HY.js";document.addEventListener("DOMContentLoaded",function(){v(),E(),w()});function v(){const t=document.getElementById("preview-template-btn"),i=document.getElementById("content"),n=document.getElementById("subject"),e=document.getElementById("template-preview-modal");if(t&&t.addEventListener("click",function(s){s.preventDefault(),f()}),i){let s;i.addEventListener("input",function(){clearTimeout(s),s=setTimeout(()=>{e&&e.classList.contains("show")&&u()},1e3)})}if(n){let s;n.addEventListener("input",function(){clearTimeout(s),s=setTimeout(()=>{e&&e.classList.contains("show")&&u()},1e3)})}}function f(){const t=document.getElementById("content").value,i=document.getElementById("subject").value,n=document.getElementById("type").value;if(!t.trim()){r.fire({title:"No Content",text:"Please enter template content before previewing.",icon:"warning",customClass:{confirmButton:"btn btn-primary"}});return}const e=d(),s=c(t,e),a=c(i,e);g(s,a,n)}function u(){const t=document.getElementById("content").value,i=document.getElementById("subject").value,n=d(),e=c(t,n),s=c(i,n),a=document.getElementById("preview-content"),o=document.getElementById("preview-subject");a&&(a.innerHTML=e),o&&(o.textContent=s||"(No subject)")}function d(){const t={};return document.querySelectorAll(".variable-input").forEach(n=>{const e=n.getAttribute("data-variable");t[e]=n.value||`[${e}]`}),{...{user_name:"John Doe",user_email:"john@example.com",company_name:"Your Company",current_date:new Date().toLocaleDateString(),current_time:new Date().toLocaleTimeString(),appointment_date:"2024-01-15",appointment_time:"10:00 AM",service_name:"Haircut",staff_name:"Jane Smith",location:"Main Branch",amount:"$50.00",invoice_number:"INV-2024-001",order_number:"ORD-2024-001",tracking_number:"TRK-123456789",reset_link:"https://example.com/reset-password?token=abc123",verification_link:"https://example.com/verify-email?token=xyz789",login_link:"https://example.com/login",support_email:"support@example.com",phone_number:"+1 (555) 123-4567",address:"123 Main St, City, State 12345"},...t}}function c(t,i){if(!t)return"";let n=t;return Object.keys(i).forEach(e=>{const s=new RegExp(`{{${e}}}`,"gi");n=n.replace(s,i[e])}),Object.keys(i).forEach(e=>{const s=new RegExp(`{${e}}`,"gi");n=n.replace(s,i[e])}),Object.keys(i).forEach(e=>{const s=new RegExp(`\\[${e}\\]`,"gi");n=n.replace(s,i[e])}),n=n.replace(/\n/g,"<br>"),n}function g(t,i,n){let e=document.getElementById("template-preview-modal");if(e){const a=document.getElementById("preview-subject"),o=document.getElementById("preview-content"),m=e.querySelector(".badge");a&&(a.textContent=i||"(No subject)"),o&&(o.innerHTML=t),m&&(m.textContent=n||"General")}else{e=document.createElement("div"),e.className="modal fade",e.id="template-preview-modal",e.innerHTML=`
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Template Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="preview-container">
                            <div class="preview-header mb-3">
                                <h6 class="text-muted mb-1">Subject:</h6>
                                <h5 id="preview-subject" class="mb-3">${i||"(No subject)"}</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">${n||"General"}</span>
                                    <small class="text-muted">Live Preview: Updates as you type</small>
                                </div>
                            </div>
                            <div class="preview-content border rounded p-4 bg-light">
                                <div id="preview-content">${t}</div>
                            </div>
                            <div class="preview-variables mt-4">
                                <h6 class="text-muted mb-2">Variable Values Used:</h6>
                                <div id="variable-values" class="row"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="send-test-email">
                            <i class="ri-send-plane-line me-1"></i> Send Test Email
                        </button>
                    </div>
                </div>
            </div>
        `,document.body.appendChild(e);const a=document.getElementById("send-test-email");a&&a.addEventListener("click",function(){j(t,i,n)})}y(),new bootstrap.Modal(e).show()}function y(){const t=document.getElementById("variable-values");if(!t)return;const i=d();let n="";Object.keys(i).forEach(e=>{n+=`
            <div class="col-md-6 mb-2">
                <div class="card card-sm">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <code class="text-primary">${e}</code>
                            <span class="text-muted small">${i[e]}</span>
                        </div>
                    </div>
                </div>
            </div>
        `}),t.innerHTML=n}function E(){document.querySelectorAll(".insert-variable").forEach(n=>{n.addEventListener("click",function(e){e.preventDefault();const s=this.getAttribute("data-variable");b(s)})}),document.getElementById("variable-list")&&h()}function b(t){const i=document.getElementById("content"),n=document.getElementById("subject"),e=`{{${t}}}`,s=document.activeElement;s===i?l(i,e):s===n?l(n,e):(l(i,e),i.focus())}function l(t,i){if(t)if(document.selection){t.focus();const n=document.selection.createRange();n.text=i}else if(t.selectionStart!==void 0){const n=t.selectionStart,e=t.selectionEnd,s=t.scrollTop;t.value=t.value.substring(0,n)+i+t.value.substring(e,t.value.length),t.selectionStart=n+i.length,t.selectionEnd=n+i.length,t.scrollTop=s,t.dispatchEvent(new Event("input",{bubbles:!0}))}else t.value+=i,t.dispatchEvent(new Event("input",{bubbles:!0}))}function h(){const t=document.getElementById("variable-list");if(!t)return;const i=[{name:"user_name",description:"Full name of the user"},{name:"user_email",description:"Email address of the user"},{name:"company_name",description:"Your company name"},{name:"current_date",description:"Current date"},{name:"current_time",description:"Current time"},{name:"appointment_date",description:"Appointment date"},{name:"appointment_time",description:"Appointment time"},{name:"service_name",description:"Service name"},{name:"staff_name",description:"Staff member name"},{name:"location",description:"Business location"},{name:"amount",description:"Payment amount"},{name:"invoice_number",description:"Invoice number"},{name:"order_number",description:"Order number"},{name:"tracking_number",description:"Tracking number"},{name:"reset_link",description:"Password reset link"},{name:"verification_link",description:"Email verification link"},{name:"login_link",description:"Login link"},{name:"support_email",description:"Support email address"},{name:"phone_number",description:"Phone number"},{name:"address",description:"Business address"}];let n='<div class="row">';i.forEach(e=>{n+=`
            <div class="col-md-6 mb-2">
                <div class="card card-sm">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <code class="text-primary">${e.name}</code>
                                <small class="text-muted d-block">${e.description}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary insert-variable" data-variable="${e.name}">
                                <i class="ri-add-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `}),n+="</div>",t.innerHTML=n,document.querySelectorAll(".insert-variable").forEach(e=>{e.addEventListener("click",function(s){s.preventDefault();const a=this.getAttribute("data-variable");b(a)})})}function w(){const t=document.getElementById("template-form");t&&t.addEventListener("submit",function(i){B()||i.preventDefault()})}function B(){const t=document.getElementById("content").value,i=document.getElementById("subject").value,n=document.getElementById("name").value;let e=!0,s=[];n.trim()||(e=!1,s.push("Template name is required")),i.trim()||(e=!1,s.push("Subject is required")),t.trim()||(e=!1,s.push("Content is required"));const a=T(t);return a.length>0&&(e=!1,s.push(`Unclosed variable syntax: ${a.join(", ")}`)),e||r.fire({title:"Validation Errors",html:`<ul class="text-start">${s.map(o=>`<li>${o}</li>`).join("")}</ul>`,icon:"error",customClass:{confirmButton:"btn btn-primary"}}),e}function T(t){const i=[{open:"{{",close:"}}"},{open:"{",close:"}"},{open:"[",close:"]"}],n=[];return i.forEach(e=>{const s=(t.match(new RegExp(p(e.open),"g"))||[]).length,a=(t.match(new RegExp(p(e.close),"g"))||[]).length;s!==a&&n.push(`${e.open}...${e.close}`)}),n}function p(t){return t.replace(/[.*+?^${}()|[\]\\]/g,"\\$&")}function j(t,i,n){r.fire({title:"Send Test Email",html:'<input type="email" id="test-email-input" class="swal2-input" placeholder="Enter recipient email">',confirmButtonText:"Send Test",showCancelButton:!0,customClass:{confirmButton:"btn btn-primary",cancelButton:"btn btn-light"},preConfirm:()=>{const e=document.getElementById("test-email-input").value;return e?S(e)?e:(r.showValidationMessage("Please enter a valid email address"),!1):(r.showValidationMessage("Please enter an email address"),!1)}}).then(e=>{e.isConfirmed&&_(e.value,t,i,n)})}function _(t,i,n,e){const s=document.getElementById("send-test-email"),a=s.innerHTML;s.disabled=!0,s.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span> Sending...',fetch("/templates/send-test",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content"),Accept:"application/json"},body:JSON.stringify({email:t,content:i,subject:n,type:e})}).then(o=>{if(!o.ok)throw new Error(`HTTP error! status: ${o.status}`);return o.json()}).then(o=>{if(o.success)r.fire({title:"Success!",text:o.message,icon:"success",customClass:{confirmButton:"btn btn-primary"}});else throw new Error(o.message||"Failed to send test email")}).catch(o=>{console.error("Error sending test email:",o),r.fire({title:"Error!",text:o.message||"Failed to send test email",icon:"error",customClass:{confirmButton:"btn btn-primary"}})}).finally(()=>{s.disabled=!1,s.innerHTML=a})}function S(t){return/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)}
