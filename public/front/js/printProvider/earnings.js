"use strict";
document.addEventListener("DOMContentLoaded",function(){
  const revealItems=Array.from(document.querySelectorAll("#walletMain > *"));
  revealItems.forEach(function(item,index){item.classList.add("section-reveal");item.style.setProperty("--reveal-delay",String(index*160)+"ms")});
  if(window.matchMedia("(prefers-reduced-motion: reduce)").matches||!("IntersectionObserver" in window)){
    revealItems.forEach(function(item){item.classList.add("is-visible")});
  }else{
    const revealObserver=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){entry.target.classList.toggle("is-visible",entry.isIntersecting)});
    },{threshold:.05,rootMargin:"0px 0px -8% 0px"});
    revealItems.forEach(function(item){revealObserver.observe(item)});
  }
  const counters=Array.from(document.querySelectorAll("[data-counter]"));
  function animateCounter(element){
    if(element.dataset.counted)return;element.dataset.counted="true";
    const target=Number(element.dataset.counter),duration=1300,start=performance.now(),formatter=new Intl.NumberFormat("en-US",{minimumFractionDigits:2,maximumFractionDigits:2});
    function update(now){const progress=Math.min((now-start)/duration,1),eased=1-Math.pow(1-progress,3);element.textContent=formatter.format(target*eased)+" ₪";if(progress<1)requestAnimationFrame(update)}
    requestAnimationFrame(update);
  }
  if(window.matchMedia("(prefers-reduced-motion: reduce)").matches){counters.forEach(function(counter){counter.dataset.counted="true"})}else if(!("IntersectionObserver" in window)){counters.forEach(animateCounter)}else{
    const counterObserver=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){animateCounter(entry.target);counterObserver.unobserve(entry.target)}})},{threshold:.45});
    counters.forEach(function(counter){counterObserver.observe(counter)});
  }
  const notificationButton=document.getElementById("notificationButton"),notificationDropdown=document.getElementById("notificationDropdown"),notificationCounter=document.getElementById("notificationCounter"),notificationList=document.getElementById("notificationList");
  const accountButton=document.getElementById("accountButton"),accountDropdown=document.getElementById("accountDropdown");
  let unreadNotifications=0;
  function shakeNotificationBell(){notificationButton.classList.remove("is-shaking");void notificationButton.offsetWidth;notificationButton.classList.add("is-shaking");setTimeout(function(){notificationButton.classList.remove("is-shaking")},600)}
  function pushNotification(message){
    if(notificationList.querySelector(".notification-empty"))notificationList.innerHTML="";
    const item=document.createElement("li");item.className="notification-item";item.textContent=message;notificationList.prepend(item);
    unreadNotifications+=1;notificationCounter.hidden=false;notificationCounter.textContent=unreadNotifications>9?"9+":String(unreadNotifications);shakeNotificationBell();
  }
  window.PalWalletNotification=pushNotification;
  window.addEventListener("palprints:notification",function(event){pushNotification(event.detail&&event.detail.message?event.detail.message:"لديك إشعار جديد")});
  function closeAccountMenu(){accountDropdown.classList.remove("open");accountDropdown.hidden=true;accountButton.setAttribute("aria-expanded","false")}
  function closeNotificationMenu(){notificationDropdown.classList.remove("open");notificationDropdown.hidden=true;notificationButton.setAttribute("aria-expanded","false")}
  accountButton.addEventListener("click",function(event){event.stopPropagation();const open=!accountDropdown.classList.contains("open");closeNotificationMenu();accountDropdown.classList.toggle("open",open);accountDropdown.hidden=!open;accountButton.setAttribute("aria-expanded",String(open))});
  notificationButton.addEventListener("click",function(event){event.stopPropagation();const open=!notificationDropdown.classList.contains("open");closeAccountMenu();notificationDropdown.classList.toggle("open",open);notificationDropdown.hidden=!open;notificationButton.setAttribute("aria-expanded",String(open));if(open){unreadNotifications=0;notificationCounter.hidden=true}});
  document.addEventListener("click",function(event){if(notificationDropdown.contains(event.target)||accountDropdown.contains(event.target))return;closeNotificationMenu();closeAccountMenu()});
  document.addEventListener("keydown",function(event){if(event.key==="Escape"){closeNotificationMenu();closeAccountMenu()}});
  document.querySelectorAll("[data-period]").forEach(function(button){button.addEventListener("click",function(){
    document.querySelectorAll("[data-period]").forEach(function(item){item.classList.remove("active")});button.classList.add("active");
    const value=button.dataset.period;document.querySelectorAll("#earningsRows tr").forEach(function(row){row.hidden=value==="today"||(!["custom","90"].includes(value)&&Number(row.dataset.days)>Number(value))});
  })});
  const dialog=document.getElementById("withdrawDialog"),amount=document.getElementById("withdrawAmount"),payoutAccount=document.getElementById("payoutAccount"),payoutAccountLabel=document.getElementById("payoutAccountLabel"),payoutAccountHint=document.getElementById("payoutAccountHint");
  const payoutMethods={
    "bank-of-palestine":{label:"رقم حساب بنك فلسطين",placeholder:"أدخل رقم الحساب البنكي",hint:"أدخل رقم الحساب المرتبط بفرع بنك فلسطين.",pattern:"[0-9]{6,20}"},
    "palpay":{label:"رقم حساب PalPay",placeholder:"أدخل رقم حساب PalPay",hint:"أدخل رقم الحساب المرتبط بمحفظة PalPay.",pattern:"[0-9]{9,10}"},
    "jawwal-pay":{label:"رقم حساب جوال بي",placeholder:"أدخل رقم حساب جوال بي",hint:"أدخل رقم الحساب المرتبط بمحفظة جوال بي.",pattern:"[0-9]{9,10}"}
  };
  function updatePayoutAccount(){
    const selected=document.querySelector('input[name="method"]:checked'),config=payoutMethods[selected.value];
    payoutAccountLabel.textContent=config.label;payoutAccount.placeholder=config.placeholder;payoutAccountHint.textContent=config.hint;payoutAccount.pattern=config.pattern;payoutAccount.value="";
  }
  document.querySelectorAll('input[name="method"]').forEach(function(method){method.addEventListener("change",updatePayoutAccount)});
  updatePayoutAccount();
  document.getElementById("openWithdraw").addEventListener("click",function(){dialog.showModal();setTimeout(function(){amount.focus()},0)});
  document.querySelectorAll("[data-close]").forEach(function(button){button.addEventListener("click",function(){dialog.close()})});
  dialog.addEventListener("click",function(event){if(event.target===dialog)dialog.close()});
  const withdrawForm = document.getElementById("withdrawForm");
  const csrf = document.querySelector('meta[name="csrf-token"]');
  let sending = false;

  function showToast(message) {
    const toast = document.getElementById("walletToast");
    if (!toast) return;
    toast.querySelector("span").textContent = message;
    toast.classList.add("show");
    setTimeout(function () { toast.classList.remove("show"); }, 3000);
  }

  withdrawForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    if (sending) return;
    if (!amount.checkValidity()) { amount.reportValidity(); return; }
    if (!payoutAccount.checkValidity()) { payoutAccount.reportValidity(); return; }

    sending = true;
    const submitButton = withdrawForm.querySelector("[type=submit]");
    if (submitButton) submitButton.disabled = true;

    try {
      const response = await fetch(withdrawForm.dataset.withdrawUrl, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf ? csrf.content : ""
        },
        body: JSON.stringify({
          amount: amount.value,
          method: document.querySelector('input[name="method"]:checked').value,
          account: payoutAccount.value
        })
      });

      if (!response.ok) {
        const error = await response.json().catch(function () { return {}; });
        const first = error.errors ? Object.values(error.errors)[0] : null;
        showToast((first && first[0]) || error.message || "تعذر إرسال طلب السحب.");
        return;
      }

      dialog.close();
      showToast("تم إرسال طلب السحب بنجاح");
      setTimeout(function () { window.location.reload(); }, 900);
    } catch (error) {
      showToast("تعذر إرسال طلب السحب.");
    } finally {
      sending = false;
      if (submitButton) submitButton.disabled = false;
    }
  });
});
