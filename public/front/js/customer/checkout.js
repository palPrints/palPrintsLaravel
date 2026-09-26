(function(){
  "use strict";
  const form=document.getElementById("checkoutForm"),panels=[...document.querySelectorAll(".step-panel")],steps=[...document.querySelectorAll(".steps li")],title=document.getElementById("pageTitle"),subtitle=document.getElementById("pageSubtitle"),summary=document.querySelector(".summary-card"),shipping=document.querySelector(".shipping-row"),toast=document.getElementById("toast");
  let current=1,timer;
  const copy={1:["إتمام الطلب","أكمل بياناتك لإتمام الطلب بسهولة وأمان"],2:["إتمام الطلب","اختر عنوان التوصيل المناسب لك"],3:["إتمام الطلب","اختر طريقة الدفع المناسبة لك"],4:["إتمام الطلب","راجع بيانات طلبك قبل تأكيده"],5:["تم تأكيد طلبك!","شكراً لك، تم استلام طلبك بنجاح"]};
  function message(text){toast.textContent=text;toast.classList.add("show");setTimeout(()=>toast.classList.remove("show"),2200)}
  function validRecipient(){let ok=true;[["fullName",3,"يرجى إدخال الاسم الكامل"],["phone",7,"يرجى إدخال رقم هاتف صحيح"]].forEach(([id,min,msg])=>{const input=document.getElementById(id),field=input.closest(".field"),valid=input.value.trim().length>=min;field.classList.toggle("invalid",!valid);field.querySelector(".error").textContent=valid?"":msg;if(!valid)ok=false});return ok}
  function updateReview(){const name=document.getElementById("fullName").value.trim()||"علاء المصري",phone=document.getElementById("phone").value.trim()||"59 123 4567",address=document.querySelector('[name="address"]:checked')?.value||"المنزل",payment=document.querySelector('[name="payment"]:checked')?.value||"palpay",paymentNames={bank:"بنك فلسطين",palpay:"PalPay",jawwal:"جوال بي"};document.getElementById("reviewCustomer").innerHTML=`${name}<br>+970 ${phone}<br>example@mail.com`;document.getElementById("reviewAddress").innerHTML=`${address}<br>غزة، الرمال، شارع الجلاء، بناية الخير، الطابق الثالث`;document.getElementById("reviewPayment").innerHTML=`الدفع عبر ${paymentNames[payment]}<br>تم التحقق من وسيلة الدفع بنجاح`}
  function showStep(n){if(n===2&&current===1&&!validRecipient())return;current=n;panels.forEach(p=>p.classList.toggle("active",Number(p.dataset.panel)===n));steps.forEach(s=>{const x=Number(s.dataset.step);s.classList.toggle("active",x===n);s.classList.toggle("done",x<n||n===5)});title.innerHTML=`${copy[n][0]} ${n===5?'<i class="bi bi-check-circle-fill" style="color:var(--success)"></i>':'<i class="bi bi-bag-lock"></i>'}`;subtitle.textContent=copy[n][1];summary.hidden=false;shipping.hidden=n<3;document.querySelector(".grand-total span").textContent=n>=3?"الإجمالي المتوقع":"الإجمالي المؤقت";if(n===4)updateReview();if(n===3&&document.querySelector('[name="payment"]:checked')?.value==="jawwal")startTimer();window.scrollTo({top:0,behavior:"smooth"})}
  document.addEventListener("click",e=>{const next=e.target.closest("[data-next]"),back=e.target.closest("[data-back]");if(next)showStep(Number(next.dataset.next));if(back)showStep(Number(back.dataset.back))});
  steps.forEach(s=>s.querySelector("button").addEventListener("click",()=>{const n=Number(s.dataset.step);if(n<=current)showStep(n)}));
  document.querySelectorAll('.choice-card input,.payment-card input').forEach(input=>input.addEventListener("change",()=>{input.closest(".address-list,.payment-options").querySelectorAll("label").forEach(x=>x.classList.remove("selected"));input.closest("label").classList.add("selected");if(input.name==="payment"){document.querySelectorAll("[data-payment-fields]").forEach(panel=>panel.hidden=panel.dataset.paymentFields!==input.value);if(input.value==="jawwal")startTimer()}}));
  document.querySelector(".add-address").addEventListener("click",()=>{const area=document.querySelector(".new-address");area.hidden=!area.hidden;if(!area.hidden)area.querySelector("input").focus()});
  document.querySelector(".new-address button").addEventListener("click",()=>{const val=document.querySelector(".new-address input").value.trim();if(val){message("تم حفظ العنوان الجديد");document.querySelector(".new-address").hidden=true}});
  function startTimer(){clearInterval(timer);let seconds=45;const el=document.getElementById("otpTimer");timer=setInterval(()=>{seconds--;el.textContent=`00:${String(seconds).padStart(2,"0")}`;if(seconds<=0){clearInterval(timer);el.textContent="إعادة الإرسال"}},1000)}
  const otp=[...document.querySelectorAll(".otp-inputs input")];otp.forEach((input,i)=>{input.addEventListener("input",()=>{input.value=input.value.replace(/\D/g,"").slice(0,1);if(input.value)otp[i+1]?.focus()});input.addEventListener("keydown",e=>{if(e.key==="Backspace"&&!input.value)otp[i-1]?.focus()})});
  function addOrderNotification(){const bell=document.getElementById("notificationsToggle"),badge=bell.querySelector(".notifications-badge"),list=document.querySelector(".notifications-list"),empty=document.querySelector(".notifications-empty");list.innerHTML='<div class="notification-item notification-item--success"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><div><p><strong>تم تأكيد طلبك بنجاح</strong><br>تم استلام الطلب #PLP-2026-000478 وجارٍ تجهيزه.</p><time>الآن</time></div></div>'+list.innerHTML;empty.hidden=true;badge.hidden=false;badge.textContent="1";bell.setAttribute("aria-label","الإشعارات، لديك إشعار جديد");bell.classList.remove("has-new-notification");void bell.offsetWidth;bell.classList.add("has-new-notification");setTimeout(()=>bell.classList.remove("has-new-notification"),900)}
  form.addEventListener("submit",e=>{e.preventDefault();document.getElementById("orderDate").textContent=new Intl.DateTimeFormat("ar-PS",{dateStyle:"long",timeStyle:"short"}).format(new Date());showStep(5);addOrderNotification();message("تم تأكيد طلبك بنجاح")});
  document.getElementById("copyOrder").addEventListener("click",async()=>{try{await navigator.clipboard.writeText("#PLP-2026-000478");message("تم نسخ رقم الطلب")}catch(_){message("رقم الطلب: #PLP-2026-000478")}});
  const sidebarToggle=document.getElementById("sidebarToggle"),sidebarClose=document.getElementById("sidebarClose"),storeSidebar=document.getElementById("storeSidebar"),sidebarBackdrop=document.getElementById("sidebarBackdrop"),sidebarLogout=document.getElementById("storeSidebarLogout");
  if(sidebarToggle&&sidebarClose&&storeSidebar&&sidebarBackdrop){
    const mobileScreen=window.matchMedia("(max-width: 991px)");
    const isMobile=()=>mobileScreen.matches;
    const syncSidebar=()=>{
      storeSidebar.hidden=false;
      if(isMobile()){
        storeSidebar.classList.remove("is-collapsed");
        storeSidebar.classList.remove("is-open");
        document.body.classList.remove("sidebar-layout-open");
        storeSidebar.setAttribute("aria-hidden","true");
      }else{
        storeSidebar.classList.remove("is-open");
        storeSidebar.classList.add("is-collapsed");
        document.body.classList.remove("sidebar-layout-open");
        storeSidebar.setAttribute("aria-hidden","true");
      }
    };
    const closeSidebar=()=>{
      storeSidebar.classList.remove("is-open");
      sidebarToggle.setAttribute("aria-expanded","false");
      sidebarToggle.setAttribute("aria-label","فتح القائمة الجانبية");
      sidebarToggle.querySelector("i")?.classList.replace("bi-x-lg","bi-list");
      if(isMobile()){
        sidebarBackdrop.hidden=true;
        storeSidebar.setAttribute("aria-hidden","true");
        document.body.style.overflow="";
      }else{
        storeSidebar.classList.add("is-collapsed");
        document.body.classList.remove("sidebar-layout-open");
        storeSidebar.setAttribute("aria-hidden","true");
      }
    };
    sidebarToggle.addEventListener("click",()=>{
      if(storeSidebar.classList.contains("is-open")){closeSidebar();return}
      storeSidebar.classList.remove("is-collapsed");
      window.requestAnimationFrame(()=>storeSidebar.classList.add("is-open"));
      sidebarToggle.setAttribute("aria-expanded","true");
      sidebarToggle.setAttribute("aria-label","إغلاق القائمة الجانبية");
      sidebarToggle.querySelector("i")?.classList.replace("bi-list","bi-x-lg");
      storeSidebar.setAttribute("aria-hidden","false");
      if(isMobile()){
        sidebarBackdrop.hidden=false;
        document.body.style.overflow="hidden";
        sidebarClose.focus();
      }else document.body.classList.add("sidebar-layout-open");
    });
    sidebarClose.addEventListener("click",closeSidebar);
    sidebarBackdrop.addEventListener("click",closeSidebar);
    sidebarClose.addEventListener("pointerenter",()=>sidebarClose.classList.add("is-hovered"));
    sidebarClose.addEventListener("pointerleave",()=>sidebarClose.classList.remove("is-hovered"));
    storeSidebar.addEventListener("click",event=>{if(event.target.closest("a")&&isMobile())closeSidebar()});
    if(sidebarLogout)sidebarLogout.addEventListener("click",()=>{if(window.confirm("هل تريد تسجيل الخروج من حسابك؟"))window.location.href=sidebarLogout.dataset.href||"login.html"});
    document.addEventListener("keydown",event=>{if(event.key!=="Escape")return;closeSidebar();sidebarToggle.focus()});
    mobileScreen.addEventListener("change",()=>{sidebarBackdrop.hidden=true;document.body.style.overflow="";sidebarToggle.setAttribute("aria-expanded","false");sidebarToggle.querySelector("i")?.classList.replace("bi-x-lg","bi-list");syncSidebar()});
    syncSidebar();
  }

  const profileToggle=document.getElementById("profileMenuToggle"),profileDropdown=document.getElementById("profileDropdown"),notificationsToggle=document.getElementById("notificationsToggle"),notificationsPanel=document.getElementById("notificationsPanel");
  function closeHeaderMenus(){profileDropdown.hidden=true;notificationsPanel.hidden=true;profileToggle.setAttribute("aria-expanded","false");notificationsToggle.setAttribute("aria-expanded","false")}
  profileToggle.addEventListener("click",e=>{e.stopPropagation();const open=profileDropdown.hidden;closeHeaderMenus();profileDropdown.hidden=!open;profileToggle.setAttribute("aria-expanded",String(open))});
  notificationsToggle.addEventListener("click",e=>{e.stopPropagation();const open=notificationsPanel.hidden;closeHeaderMenus();notificationsPanel.hidden=!open;notificationsToggle.setAttribute("aria-expanded",String(open))});
  document.addEventListener("click",e=>{if(!e.target.closest(".profile-menu,.notifications-menu"))closeHeaderMenus()});
  document.querySelector(".profile-dropdown__logout")?.addEventListener("click",()=>{if(window.confirm("هل تريد تسجيل الخروج من حسابك؟"))window.location.href="login.html"});
  document.querySelector(".notifications-clear")?.addEventListener("click",()=>{document.querySelector(".notifications-list").innerHTML="";document.querySelector(".notifications-empty").hidden=false;const badge=document.querySelector(".notifications-badge");if(badge)badge.hidden=true;message("تم مسح الإشعارات")});
})();
