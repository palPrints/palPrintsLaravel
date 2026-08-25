/* Shared fallback translator for every visible string not owned by page scripts. */
(() => {
  "use strict";
  const pairs = {
    "إنشاء حساب جديد":"Create a new account","اختر نوع الحساب الذي يناسبك وابدأ رحلتك معنا":"Choose the account type that suits you and start your journey","الخطوة 1 من 2":"Step 1 of 2","العميل":"Customer","المصمم":"Designer","المطبعة":"Print shop","اطلب منتجات مطبوعة بسهولة":"Order printed products with ease","ارفع تصاميمك واكسب المال":"Upload your designs and earn money","استقبل الطلبات وابدأ الطباعة":"Receive orders and start printing","المعلومات الشخصية":"Personal information","الاسم الكامل":"Full name","ادخل اسمك الكامل":"Enter your full name","البريد الإلكتروني":"Email address","رقم الهاتف":"Phone number","كلمة المرور":"Password","ادخل كلمة المرور":"Enter your password","تأكيد كلمة المرور":"Confirm password","أعد إدخال كلمة المرور":"Re-enter your password","أوافق على":"I agree to","الشروط والأحكام":"Terms and conditions","إنشاء الحساب":"Create account","أو":"Or","تسجيل باستخدام Google":"Sign up with Google","تسجيل باستخدام Apple":"Sign up with Apple","تسجيل باستخدام Facebook":"Sign up with Facebook","لديك حساب بالفعل؟":"Already have an account?","تسجيل الدخول":"Sign in","التخصص":"Specialization","اختر تخصصك":"Choose your specialization","تصميم جرافيك":"Graphic design","تصميم ملابس":"Fashion design","رسم وتوضيح":"Illustration","تصميم منتجات":"Product design","رابط معرض أعمالك (اختياري)":"Portfolio link (optional)","نبذة قصيرة عنك (اختياري)":"Short bio (optional)","اكتب نبذة مختصرة عنك وخبراتك":"Write a short bio about you and your experience","اسم المطبعة / النشاط التجاري":"Print shop / business name","ادخل اسم المطبعة أو النشاط التجاري":"Enter the print shop or business name","نوع المنتجات التي تطبعها (اختياري)":"Products you print (optional)","مثال: ملابس، أكواب، لوحات، ملصقات":"Example: apparel, mugs, posters, stickers","موقع المطبعة (اختياري)":"Print shop location (optional)","المدينة / الدولة":"City / country","نبذة قصيرة عن المطبعة (اختياري)":"Short print shop bio (optional)","اكتب نبذة مختصرة عن خدمات مطبعتك وخبراتك":"Describe your print services and experience",
    "نسيت كلمة المرور؟":"Forgot your password?","لا تقلق، أدخل بريدك الإلكتروني وسنرسل إليك رمز تحقق لإعادة تعيين كلمة المرور.":"Don't worry. Enter your email and we'll send you a verification code to reset your password.","إرسال رمز التحقق":"Send verification code","العودة إلى تسجيل الدخول":"Back to sign in","المساعدة":"Help","سياسة الخصوصية":"Privacy policy","روابط قانونية":"Legal links","1 من 3":"1 of 3",
    "تحقق من الرمز":"Verify the code","تم إرسال رمز مكوّن من 6 أرقام إلى":"A 6-digit code was sent to","تغيير البريد الإلكتروني":"Change email address","انتهاء الصلاحية خلال":"Expires in","لم يصلك الرمز؟":"Didn't receive the code?","إعادة إرسال الرمز":"Resend code","العودة":"Back","2 من 3":"2 of 3","رمز التحقق المكون من ستة أرقام":"Six-digit verification code","الرقم الأول":"First digit","الرقم الثاني":"Second digit","الرقم الثالث":"Third digit","الرقم الرابع":"Fourth digit","الرقم الخامس":"Fifth digit","الرقم السادس":"Sixth digit",
    "إعادة تعيين كلمة المرور":"Reset password","يرجى إدخال كلمة المرور الجديدة وتأكيدها.":"Enter and confirm your new password.","كلمة المرور الجديدة":"New password","أدخل كلمة المرور الجديدة":"Enter your new password","أعد إدخال كلمة المرور":"Re-enter your password","حفظ كلمة المرور":"Save password","جميع الحقوق محفوظة.":"All rights reserved.","تبديل المظهر":"Toggle appearance","تغيير اللغة إلى الإنجليزية":"Switch language to English","تغيير اللغة إلى العربية":"Switch language to Arabic","خيارات الحساب والواجهة":"Account and interface options","خيارات الواجهة":"Interface options","شعار PALPRINTS":"PALPRINTS logo","انتقل إلى المحتوى الرئيسي":"Skip to main content","التحقق الأمني":"Security verification","استعادة الحساب":"Account recovery",
    "حالة الحساب":"Account status","بانتظار مراجعة حسابك":"Your account is pending review","تم استلام طلبك بنجاح، ويقوم فريق PALPRINTS الآن بمراجعة بيانات حسابك.":"Your request was received successfully. The PALPRINTS team is now reviewing your account details.","ماذا يحدث الآن؟":"What happens now?","نراجع البيانات المرسلة للتأكد من جاهزية الحساب قبل تفعيل الصلاحيات.":"We review the submitted information to ensure the account is ready before activation.","سنرسل لك إشعارًا عبر البريد الإلكتروني عند اكتمال المراجعة أو إذا احتجنا معلومات إضافية.":"We'll email you when the review is complete or if we need more information.","تم استلام الطلب":"Request received","قيد المراجعة":"Under review","تفعيل الحساب":"Account activation","نوع الحساب":"Account type","رقم الطلب":"Request reference","تصفح المنصة كزائر":"Browse as guest"
  };
  const reverse = Object.fromEntries(Object.entries(pairs).map(([ar,en]) => [en,ar]));
  const attrs = ["placeholder","title","aria-label"];
  const clean = value => value?.trim();
  function language(){ return document.documentElement.lang === "en" ? "en" : "ar"; }
  function translateValue(value, lang){
    const key = clean(value); if (!key) return value;
    const translated = lang === "en" ? pairs[key] : reverse[key];
    if (!translated) return value;
    return value.replace(key, translated);
  }
  function apply(root=document){
    const lang=language();
    const walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,{acceptNode(node){
      return node.parentElement && !/^(SCRIPT|STYLE|NOSCRIPT)$/.test(node.parentElement.tagName) && clean(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
    }});
    const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node=>{node.nodeValue=translateValue(node.nodeValue,lang)});
    (root.querySelectorAll?.("*")||[]).forEach(el=>attrs.forEach(attr=>{if(el.hasAttribute(attr))el.setAttribute(attr,translateValue(el.getAttribute(attr),lang))}));
    document.documentElement.dir=lang === "en" ? "ltr" : "rtl";
  }
  function refresh(){queueMicrotask(()=>apply(document))}
  document.addEventListener("DOMContentLoaded",()=>{
    apply(); document.getElementById("languageToggle")?.addEventListener("click",()=>setTimeout(refresh,0));
    new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1)apply(node)}))).observe(document.body,{childList:true,subtree:true});
  });
})();
