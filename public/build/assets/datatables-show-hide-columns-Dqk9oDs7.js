import{D as s}from"./dataTables.responsive-CxDTCFXv.js";import"./responsive.bootstrap5-DyO5HYax.js";import"./jquery-UIAagU1C.js";import"./_commonjsHelpers-CE1G-McA.js";document.addEventListener("DOMContentLoaded",function(){const n=document.getElementById("show-hide-column");if(n){const c=new s(n,{responsive:!0,dom:"<'d-md-flex justify-content-between align-items-center mt-2 mb-3'<'columnToggleWrapper'B>f>rt<'d-md-flex justify-content-between align-items-center mt-2'lp>",language:{paginate:{first:'<i class="ti ti-chevrons-left"></i>',previous:'<i class="ti ti-chevron-left"></i>',next:'<i class="ti ti-chevron-right"></i>',last:'<i class="ti ti-chevrons-right"></i>'}}}),i=["Company","Symbol","Price","Change","Volume","Market Cap","Rating","Status"],l=document.querySelector(".columnToggleWrapper");if(l){const o=document.createElement("div");o.className="dropdown",o.innerHTML=`
        <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            Show/Hide Columns
        </button>
        <ul class="dropdown-menu" id="columnToggleMenu">
            ${i.map((e,t)=>`
                <li class="dropdown-item">
                    <div class="form-check">
                        <input class="form-check-input form-check-input-light fs-14 mt-0 toggle-vis" 
                               type="checkbox" data-column="${t}" id="colToggle${t}" checked>
                        <label class="form-check-label fw-medium" for="colToggle${t}">
                            ${e}
                        </label>
                    </div>
                </li>
            `).join("")}
        </ul>
    `,l.appendChild(o),document.getElementById("columnToggleMenu").addEventListener("change",function(e){if(e.target.classList.contains("toggle-vis")){const t=parseInt(e.target.dataset.column,10);c.column(t).visible(e.target.checked)}})}}});
