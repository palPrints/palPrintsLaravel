@php
    $viewer = auth()->user();
    $toRole = fn (string $role, string $route) => $viewer && ! $viewer->hasRole($role) ? route('dashboard') : route($route);
@endphp
<!doctype html>
<html lang="ar" dir="rtl" data-bs-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>عن PALPRINTS</title>
    <meta name="description" content="PalPrints منصة فلسطينية للطباعة عند الطلب، تجمع العملاء والمصممين والمطابع وشركاء التوصيل في مكان واحد." />
    <link rel="stylesheet" href="{{ asset('front/home/css/home.css') }}?v={{ hash_file('sha256', public_path('front/home/css/home.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/home/css/about.css') }}?v={{ filemtime(public_path('front/home/css/about.css')) }}" />
    <link rel="stylesheet" href="{{ asset('front/shared/theme-dark.css') }}?v={{ filemtime(public_path('front/shared/theme-dark.css')) }}" />
    <script src="{{ asset('front/shared/theme-dark.js') }}?v={{ filemtime(public_path('front/shared/theme-dark.js')) }}"></script>
  </head>
  <body class="home-page about-page">
    @include('partials.home-header', ['active' => 'about'])

    <main class="about-main">
      <article class="about-card-page" aria-labelledby="about-title">
        <section class="about-block">
          <h1 id="about-title">من هي PalPrints؟</h1>
          <p>PalPrints منصة فلسطينية للطباعة عند الطلب، صُممت لتجمع العملاء والمصممين والمطابع وشركاء التوصيل في مكان واحد، حتى تتحول أي فكرة إلى منتج مطبوع يصل إلى بابك، دون الحاجة إلى مخزون أو معدات.</p>
          <p>من خلال PalPrints يمكنك اختيار المنتج، وتخصيصه بتصميمك أو بتصميم من مصممينا، وإتمام طلبك بسهولة، ثم تتولى المطبعة الطباعة ويتولى شركاء التوصيل إيصاله إليك.</p>
        </section>

        <section class="about-block">
          <h2>فكرتنا</h2>
          <p>بدأت PalPrints من حاجة بسيطة: أن يجد المصمم الفلسطيني مكانًا يعرض فيه إبداعه ويكسب منه دون عناء الإنتاج، وأن يجد العميل منتجات مميزة بتصاميم محلية يخصصها كما يحب.</p>
          <p>الطباعة عند الطلب تعني أن كل قطعة تُطبع بعد أن تُطلب فقط، فلا مخزون ولا هدر. لذلك عملنا على بناء تجربة منظمة تربط كل الأطراف خطوة بخطوة.</p>
        </section>

        <section class="about-block">
          <h2>ماذا نقدم؟</h2>
          <p>نوفر مجموعة من الخدمات للأطراف الثلاثة، ومنها:</p>
          <ul class="about-list">
            <li>طباعة تيشيرتات وهوديز وأكواب وحقائب قماشية ودفاتر وبوسترات وكفرات موبايل وستيكرات.</li>
            <li>استوديو تصميم لتخصيص المنتج برفع تصميمك أو التعديل على تصميم جاهز.</li>
            <li>تصاميم جاهزة من مصممين محليين يمكن طباعتها على منتجات متنوعة.</li>
            <li>لوحة أرباح ومبيعات لحظية للمصممين، وإشعار فوري عند اعتماد التصميم.</li>
            <li>طلبات جاهزة للتنفيذ للمطابع، مع ملفات الطباعة وبيانات الشحن.</li>
            <li>تتبّع الطلب من الدفع حتى التسليم، وتوصيل إلى بابك عبر شركاء التوصيل.</li>
          </ul>
        </section>

        <section class="about-block">
          <h2>كيف تطلب من PalPrints؟</h2>
          <ol class="about-steps-list">
            <li>
              <h3>اختر المنتج</h3>
              <p>تصفّح المنتجات المتاحة واختر ما يناسبك من النوع واللون والمقاس.</p>
            </li>
            <li>
              <h3>اختر التصميم أو صمّم بنفسك</h3>
              <p>اختر تصميمًا من مصممينا، أو ارفع تصميمك وعدّله في الاستوديو.</p>
            </li>
            <li>
              <h3>راجع طلبك</h3>
              <p>انتقل إلى السلة وراجع المنتجات والكميات والتفاصيل قبل إتمام الطلب.</p>
            </li>
            <li>
              <h3>أكمل طلبك</h3>
              <p>أدخل البيانات المطلوبة، واختر طريقة الدفع والتوصيل، ثم تابع عملية الدفع.</p>
            </li>
            <li>
              <h3>استلم منتجك</h3>
              <p>تطبع المطبعة طلبك ويصلك إلى بابك، وتتابع حالته أولًا بأول.</p>
            </li>
          </ol>
        </section>

        <section class="about-block">
          <h2>لماذا PalPrints؟</h2>
          <h3>طباعة حسب الطلب</h3>
          <p>كل قطعة تُطبع بعد أن تُطلب، فلا مخزون ولا هدر ولا التزامات مسبقة.</p>
          <h3>دعم المصممين المحليين</h3>
          <p>نمنح المصممين مساحة يعرضون فيها أعمالهم ويكسبون منها، من دون معدات أو تكلفة إنتاج.</p>
          <h3>جودة واحترافية</h3>
          <p>نعمل مع مطابع تحوّل ملفات التصميم إلى منتجات مطبوعة بعناية.</p>
          <h3>تجربة سهلة وواضحة</h3>
          <p>خطوات مرتبة وشروحات مبسطة داخل كل صفحة، من اختيار المنتج حتى التسليم.</p>
          <h3>توصيل إلى بابك</h3>
          <p>شراكات توصيل تُوصل الطلب إلى العميل أينما كان.</p>
        </section>

        <section class="about-block">
          <h2>لمن تناسب PalPrints؟</h2>
          <p>المنصة مناسبة لمختلف الاحتياجات، ومنها:</p>
          <ul class="about-list">
            <li>العملاء الذين يريدون منتجات مخصصة أو هدايا بتصاميم مميزة.</li>
            <li>المصممون الذين يريدون بيع أعمالهم على منتجات متنوعة دون مخزون.</li>
            <li>المطابع التي تريد استقبال طلبات جاهزة للتنفيذ وزيادة مبيعاتها.</li>
            <li>الشركات والفرق والمبادرات التي تحتاج منتجات بشعارها وهويتها.</li>
          </ul>
        </section>

        <section class="about-block">
          <h2>رؤيتنا</h2>
          <p>أن نكون المنصة الأولى للطباعة عند الطلب في فلسطين، وأن نجعل الطريق من الفكرة إلى المنتج سهلًا وواضحًا لكل من يملك فكرة.</p>
        </section>

        <section class="about-block">
          <h2>رسالتنا</h2>
          <p>ربط العملاء والمصممين والمطابع في منصة واحدة، وتقديم منتجات مطبوعة بجودة عالية وخيارات واضحة وتجربة استخدام بسيطة.</p>
        </section>

        <section class="about-block about-block--closing">
          <h3>جاهز تبدأ مع PalPrints؟</h3>
          <p>اختر منتجك، أو ارفع تصاميمك، أو سجّل مطبعتك، واترك الباقي علينا.</p>
          <div class="about-actions">
            <a class="about-button about-button--primary" href="{{ $toRole('customer', 'customer.store') }}">ابدأ التسوق</a>
            <a class="about-button about-button--outline" href="{{ $toRole('designer', 'designer.designs.create') }}">ابدأ التصميم</a>
            <a class="about-button about-button--outline" href="{{ route('register') }}">سجّل مطبعتك</a>
          </div>
          <p class="about-help">للمساعدة، <a href="{{ $toRole('customer', 'customer.support') }}">تواصل معنا</a></p>
        </section>
      </article>
    </main>

    @include('partials.home-footer')

    <script src="{{ asset('front/home/js/home.js') }}?v={{ filemtime(public_path('front/home/js/home.js')) }}"></script>
  @include('partials.page-loader')
  </body>
</html>
