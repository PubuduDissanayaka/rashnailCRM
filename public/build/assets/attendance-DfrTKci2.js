const m=()=>document.querySelector('meta[name="csrf-token"]')?.getAttribute("content"),c=(a,e="GET",t=null)=>{const s={method:e,headers:{"Content-Type":"application/json","X-CSRF-TOKEN":m(),"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}};return t&&(s.body=JSON.stringify(t)),fetch(a,s).then(r=>r.json())},n=(a,e,t="")=>{typeof Swal<"u"?Swal.fire({icon:a,title:e,text:t,timer:2500,showConfirmButton:!1,timerProgressBar:!0}):alert(`${e} ${t}`)};window.viewAttendance=function(a){c(`/api/attendance/${a}/view`).then(e=>{if(!e.success){n("error","Failed to load",e.message);return}const t=e.attendance,r={present:"success",late:"warning",absent:"danger",leave:"info",half_day:"secondary"}[t.status]||"secondary";let u=t.breaks.length?t.breaks.map(o=>`<tr><td><span class="badge bg-secondary">${o.type}</span></td><td>${o.start}</td><td>${o.end}</td><td>${o.duration}</td></tr>`).join(""):'<tr><td colspan="4" class="text-muted text-center">No breaks recorded</td></tr>',b=t.audit_logs.length?t.audit_logs.map(o=>`<tr><td><span class="badge bg-light text-dark">${o.action}</span></td><td>${o.user}</td><td class="text-muted">${o.at}</td></tr>`).join(""):'<tr><td colspan="3" class="text-muted text-center">No audit logs</td></tr>',d="";t.latitude&&t.longitude&&(d+=`<a href="https://maps.google.com/?q=${t.latitude},${t.longitude}" target="_blank" class="btn btn-sm btn-outline-success me-1"><i class="ti ti-map-pin me-1"></i>Check-in Location</a>`),t.latitude_out&&t.longitude_out&&(d+=`<a href="https://maps.google.com/?q=${t.latitude_out},${t.longitude_out}" target="_blank" class="btn btn-sm btn-outline-info"><i class="ti ti-map-pin me-1"></i>Check-out Location</a>`),d||(d='<span class="text-muted">Location not recorded</span>');const k=t.late_arrival_minutes>0?`<span class="badge bg-warning-subtle text-warning ms-2">${t.late_arrival_minutes}m late</span>`:"";document.getElementById("attendanceModalBody").innerHTML=`
            <div class="row g-3">
                <div class="col-12"><h5 class="fw-bold">${t.user_name} <small class="text-muted fw-normal">${t.user_email}</small></h5></div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Date</label>
                    <p class="fw-bold mb-0">${t.date}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Status</label>
                    <p class="mb-0"><span class="badge bg-${r}-subtle text-${r}">${t.status.replace("_"," ").replace(/\b\w/g,o=>o.toUpperCase())}</span>
                    ${t.is_approved?'<span class="badge bg-success ms-1">Approved</span>':'<span class="badge bg-secondary ms-1">Pending</span>'}
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Hours Worked</label>
                    <p class="fw-bold mb-0">${t.hours_worked}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Check-in</label>
                    <p class="mb-0">${t.check_in?`<span class="badge bg-success-subtle text-success">${t.check_in}</span>${k}`:'<span class="text-muted">-</span>'}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Check-out</label>
                    <p class="mb-0">${t.check_out?`<span class="badge bg-info-subtle text-info">${t.check_out}</span>`:'<span class="text-muted">-</span>'}</p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-1">Notes</label>
                    <p class="mb-0">${t.notes||'<span class="text-muted">No notes</span>'}</p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-1">Location</label>
                    <div>${d}</div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-2 d-block">Breaks</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light"><tr><th>Type</th><th>Start</th><th>End</th><th>Duration</th></tr></thead>
                            <tbody>${u}</tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-2 d-block">Audit Trail</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light"><tr><th>Action</th><th>By</th><th>At</th></tr></thead>
                            <tbody>${b}</tbody>
                        </table>
                    </div>
                </div>
            </div>`,document.getElementById("attendanceModal").dataset.attendanceId=a,new bootstrap.Modal(document.getElementById("attendanceModal")).show()}).catch(()=>n("error","Network Error","Could not load attendance details."))};window.editAttendanceRecord=function(a){window.location.href=`/attendance/manual/${a}/edit`};window.deleteAttendanceRecord=function(a){if(typeof Swal>"u"){if(!confirm("Are you sure you want to delete this attendance record?"))return;f(a);return}Swal.fire({icon:"warning",title:"Delete Attendance?",text:"This action cannot be undone.",showCancelButton:!0,confirmButtonColor:"#dc3545",confirmButtonText:"Yes, delete it",cancelButtonText:"Cancel"}).then(e=>{e.isConfirmed&&f(a)})};function f(a){c(`/attendance/${a}`,"DELETE").then(e=>{e.success?(n("success","Deleted!","Attendance record removed."),setTimeout(()=>location.reload(),1200)):n("error","Delete Failed",e.message||"Unknown error")}).catch(()=>n("error","Network Error","Could not delete this record."))}window.approveAttendance=function(a){if(typeof Swal>"u"){if(!confirm("Approve this attendance record?"))return;h(a);return}Swal.fire({icon:"question",title:"Approve Attendance?",showCancelButton:!0,confirmButtonColor:"#198754",confirmButtonText:"Approve"}).then(e=>{e.isConfirmed&&h(a)})};function h(a){c(`/api/attendance/${a}/approve`,"POST").then(e=>{e.success?(n("success","✅ Approved!","Attendance record has been approved."),setTimeout(()=>location.reload(),1200)):n("error","Approval Failed",e.message||"Unknown error")}).catch(()=>n("error","Network Error","Could not approve this record."))}window.rejectAttendance=function(a){if(typeof Swal>"u"){const e=prompt("Enter rejection reason:");if(!e)return;g(a,e);return}Swal.fire({icon:"warning",title:"Reject Attendance",input:"textarea",inputLabel:"Reason for rejection",inputPlaceholder:"Enter reason...",inputAttributes:{"aria-label":"Rejection reason"},showCancelButton:!0,confirmButtonColor:"#dc3545",confirmButtonText:"Reject",preConfirm:e=>((!e||e.trim().length<3)&&Swal.showValidationMessage("Please enter a reason (at least 3 characters)"),e)}).then(e=>{e.isConfirmed&&g(a,e.value)})};function g(a,e){c(`/api/attendance/${a}/reject`,"POST",{reason:e}).then(t=>{t.success?(n("success","❌ Rejected","Attendance record has been rejected."),setTimeout(()=>location.reload(),1200)):n("error","Rejection Failed",t.message||"Unknown error")}).catch(()=>n("error","Network Error","Could not reject this record."))}let l=null,i=null;window.startBreak=function(a="lunch"){c("/api/attendance/break/start","POST",{break_type:a}).then(e=>{e.success?(n("success","☕ Break Started",`Break started at ${e.start_time}`),i=new Date,w(),p(!0)):n("error","Break Failed",e.message)}).catch(()=>n("error","Network Error","Could not start break."))};window.endBreak=function(){c("/api/attendance/break/end","POST").then(a=>{a.success?(n("success","🔄 Break Ended","Welcome back!"),v(),p(!1)):n("error","Break Failed",a.message)}).catch(()=>n("error","Network Error","Could not end break."))};function w(){l&&clearInterval(l),l=setInterval(()=>{if(!i)return;const a=Math.floor((new Date-i)/1e3),e=Math.floor(a/60),t=a%60,s=document.getElementById("break-timer");s&&(s.textContent=`${String(e).padStart(2,"0")}:${String(t).padStart(2,"0")}`)},1e3)}function v(){l&&clearInterval(l),l=null,i=null;const a=document.getElementById("break-timer");a&&(a.textContent="00:00")}function p(a){const e=document.getElementById("start-break-btn"),t=document.getElementById("end-break-btn"),s=document.getElementById("break-timer-wrap");e&&(e.style.display=a?"none":""),t&&(t.style.display=a?"":"none"),s&&(s.style.display=a?"":"none")}window.bulkApprove=function(){const a=[...document.querySelectorAll(".attendance-checkbox:checked")];if(!a.length){n("warning","No records selected","Please select at least one attendance record to approve.");return}const e=a.map(t=>t.dataset.id);Swal.fire({icon:"question",title:`Approve ${e.length} record(s)?`,showCancelButton:!0,confirmButtonColor:"#198754",confirmButtonText:"Approve All"}).then(async t=>{if(!t.isConfirmed)return;let s=0,r=0;for(const u of e)(await c(`/api/attendance/${u}/approve`,"POST")).success?s++:r++;n("success","Bulk Approval",`${s} approved${r?`, ${r} failed`:""}.`),setTimeout(()=>location.reload(),1500)})};document.addEventListener("DOMContentLoaded",function(){fetch("/api/attendance/break/status",{headers:{"X-CSRF-TOKEN":m(),Accept:"application/json","X-Requested-With":"XMLHttpRequest"}}).then(e=>e.json()).then(e=>{e.on_break&&e.break&&(i=new Date(e.break.start_time),w(),p(!0))}).catch(()=>{});const a=document.getElementById("select-all-checkbox");a&&a.addEventListener("change",function(){document.querySelectorAll(".attendance-checkbox").forEach(e=>e.checked=this.checked)}),setInterval(()=>{if(!document.getElementById("attendance-stats-bar"))return;const t=document.getElementById("attendance-date")?.value||"";fetch(`/api/attendance/today-status?date=${t}`,{headers:{"X-CSRF-TOKEN":m(),Accept:"application/json","X-Requested-With":"XMLHttpRequest"}}).then(s=>s.json()).catch(()=>{})},6e4)});
