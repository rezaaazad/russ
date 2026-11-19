<?php
/**
 * Packaged en / ru / ar copy for the wireframe homepage.
 *
 * @package LifeRuss
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overlay for one language.
 *
 * @param string $lang en|ru|ar.
 * @return array
 */
function liferuss_home_v2_i18n( $lang ) {
	$all = array(
		'en' => array(
			'announce_text'         => 'Free consultation on studying and moving to Russia — request yours now',
			'hv2_hero_cta_text'     => 'Take the first step',
			'hv2_hero_video_label'  => 'Play intro video',
			'hv2_services_title'    => 'Our services',
			'hv2_services_subtitle' => 'From the first consultation to settling in, we guide every step clearly.',
			'hv2_services'          => array(
				array( 'title' => 'Study', 'text' => 'Admission to state and medical universities in Russia, from choosing a major to enrolment.' ),
				array( 'title' => 'Visa', 'text' => 'Invitation, embassy appointment, and student visa follow-up until the entry stamp.' ),
				array( 'title' => 'Trade and sourcing', 'text' => 'Sourcing, buying, and exporting goods between Iran and Russia with clear tracking.' ),
				array( 'title' => 'Work', 'text' => 'A guide to work and residence routes in Russia for people planning to relocate.' ),
			),
			'hv2_offers_title'      => 'Our migration options',
			'hv2_offers_subtitle'   => 'Pick the route that fits you and read the details on its own page.',
			'hv2_offers_btn'        => 'View all',
			'hv2_offers'            => array(
				array( 'tag' => 'Study', 'title' => 'Study in Russia', 'text' => 'Bachelor to PhD at state and medical universities in Russia.' ),
				array( 'tag' => 'Study', 'title' => 'Direct admission', 'text' => 'Straight into the main programme for eligible applicants.' ),
				array( 'tag' => 'Study', 'title' => 'Preparatory year', 'text' => 'Russian-language and foundation courses before your main degree.' ),
				array( 'tag' => 'Visa', 'title' => 'Student visa', 'text' => 'Invitation, embassy appointment, and follow-up until the visa is issued.' ),
				array( 'tag' => 'Migration', 'title' => 'Work in Russia', 'text' => 'Routes and conditions for working in Russia.' ),
				array( 'tag' => 'Migration', 'title' => 'Residence in Russia', 'text' => 'A guide to residence types and how to obtain them.' ),
				array( 'tag' => 'Migration', 'title' => 'Russian citizenship', 'text' => 'Conditions and steps for the lawful routes to citizenship.' ),
			),
			'hv2_pay_title'         => 'Start your migration path with *instalments*',
			'hv2_pay_subtitle'      => 'Plan costs clearly, in step with your case. Your adviser confirms the exact terms during the consultation.',
			'hv2_pay_cta'           => 'Request a consultation',
			'hv2_pay_items'         => array(
				array( 'title' => 'Staged payments', 'text' => 'Pay in step with the stages of your case.' ),
				array( 'title' => 'Suitable instalments', 'text' => 'Instalment terms are set after your case is reviewed, in the consultation.' ),
				array( 'title' => 'Cost control', 'text' => 'Get a clear cost estimate before you begin.' ),
			),
			'hv2_cases_title'       => 'Success stories',
			'hv2_cases_subtitle'    => 'Examples of paths completed with LifeRuss.',
			'hv2_cases_btn'         => 'View all',
			'hv2_tm_title'          => 'What our clients say',
			'hv2_tm_subtitle'       => 'Experiences from people who completed their path with LifeRuss.',
			'hv2_ig_title'          => 'Our community on *Instagram*',
			'hv2_ig_text'           => 'Follow stories, news, and practical tips about moving and studying in Russia.',
			'hv2_ig_btn'            => 'Follow on Instagram',
			'hv2_app_title'         => 'Free language-learning app',
			'hv2_app_text'          => 'Start learning Russian today with our free app.',
			'hv2_app_btn'           => 'Get the app',
			'hv2_blog_title'        => 'Latest migration articles',
			'hv2_blog_btn'          => 'View all',
		),
		'ru' => array(
			'announce_text'         => 'Бесплатная консультация по обучению и переезду в Россию — оставьте заявку сейчас',
			'hv2_hero_cta_text'     => 'Сделать первый шаг',
			'hv2_hero_video_label'  => 'Смотреть видео',
			'hv2_services_title'    => 'Наши услуги',
			'hv2_services_subtitle' => 'От первой консультации до обустройства — каждый шаг понятен и прозрачен.',
			'hv2_services'          => array(
				array( 'title' => 'Обучение', 'text' => 'Поступление в государственные и медицинские вузы России: от выбора специальности до зачисления.' ),
				array( 'title' => 'Виза', 'text' => 'Приглашение, запись в посольство и сопровождение студенческой визы до въезда.' ),
				array( 'title' => 'Торговля и поставки', 'text' => 'Поиск поставщиков, закупки и экспорт товаров между Ираном и Россией с понятным отслеживанием.' ),
				array( 'title' => 'Работа', 'text' => 'Путеводитель по вариантам работы и проживания в России для тех, кто планирует переезд.' ),
			),
			'hv2_offers_title'      => 'Наши предложения по переезду',
			'hv2_offers_subtitle'   => 'Выберите подходящий путь и изучите подробности на его странице.',
			'hv2_offers_btn'        => 'Смотреть все',
			'hv2_offers'            => array(
				array( 'tag' => 'Обучение', 'title' => 'Учёба в России', 'text' => 'От бакалавриата до докторантуры в государственных и медицинских вузах.' ),
				array( 'tag' => 'Обучение', 'title' => 'Прямое поступление', 'text' => 'Сразу на основную программу для подходящих кандидатов.' ),
				array( 'tag' => 'Обучение', 'title' => 'Подготовительный курс', 'text' => 'Русский язык и базовые предметы перед основной программой.' ),
				array( 'tag' => 'Виза', 'title' => 'Студенческая виза', 'text' => 'Приглашение, запись в посольство и сопровождение до получения визы.' ),
				array( 'tag' => 'Переезд', 'title' => 'Работа в России', 'text' => 'Варианты и условия трудоустройства в России.' ),
				array( 'tag' => 'Переезд', 'title' => 'Проживание в России', 'text' => 'Виды разрешений на проживание и порядок их получения.' ),
				array( 'tag' => 'Переезд', 'title' => 'Гражданство России', 'text' => 'Условия и этапы получения гражданства законными путями.' ),
			),
			'hv2_pay_title'         => 'Начните путь к переезду с *оплатой в рассрочку*',
			'hv2_pay_subtitle'      => 'Планируйте расходы прозрачно, по этапам дела. Точные условия консультант назовёт на консультации.',
			'hv2_pay_cta'           => 'Заказать консультацию',
			'hv2_pay_items'         => array(
				array( 'title' => 'Поэтапная оплата', 'text' => 'Платите по мере продвижения вашего дела.' ),
				array( 'title' => 'Удобная рассрочка', 'text' => 'Условия рассрочки определяются после изучения дела, на консультации.' ),
				array( 'title' => 'Контроль расходов', 'text' => 'Понятная смета до начала работы.' ),
			),
			'hv2_cases_title'       => 'Успешные кейсы',
			'hv2_cases_subtitle'    => 'Примеры путей, пройденных с LifeRuss.',
			'hv2_cases_btn'         => 'Смотреть все',
			'hv2_tm_title'          => 'Отзывы наших клиентов',
			'hv2_tm_subtitle'       => 'Опыт тех, кто прошёл свой путь с LifeRuss.',
			'hv2_ig_title'          => 'Наше сообщество в *Instagram*',
			'hv2_ig_text'           => 'Истории, новости и полезные советы о переезде и учёбе в России.',
			'hv2_ig_btn'            => 'Подписаться в Instagram',
			'hv2_app_title'         => 'Бесплатное приложение для изучения языка',
			'hv2_app_text'          => 'Начните учить русский уже сегодня с нашим бесплатным приложением.',
			'hv2_app_btn'           => 'Скачать приложение',
			'hv2_blog_title'        => 'Свежие материалы о переезде',
			'hv2_blog_btn'          => 'Смотреть все',
		),
		'ar' => array(
			'announce_text'         => 'استشارة مجانية حول الدراسة والهجرة إلى روسيا — اطلب الآن',
			'hv2_hero_cta_text'     => 'ابدأ الخطوة الأولى',
			'hv2_hero_video_label'  => 'تشغيل الفيديو التعريفي',
			'hv2_services_title'    => 'خدمات مؤسستنا',
			'hv2_services_subtitle' => 'من الاستشارة الأولى حتى الاستقرار، نرافقك بخطوات واضحة وشفافة.',
			'hv2_services'          => array(
				array( 'title' => 'الدراسة', 'text' => 'القبول في الجامعات الحكومية والطبية في روسيا، من اختيار التخصص حتى التسجيل.' ),
				array( 'title' => 'التأشيرة', 'text' => 'الدعوة وموعد السفارة ومتابعة التأشيرة الدراسية حتى ختم الدخول.' ),
				array( 'title' => 'التجارة والتوريد', 'text' => 'البحث عن الموردين وشراء وتصدير البضائع بين إيران وروسيا مع متابعة واضحة.' ),
				array( 'title' => 'العمل', 'text' => 'دليل مسارات العمل والإقامة في روسيا لمن يخطط للهجرة.' ),
			),
			'hv2_offers_title'      => 'عروضنا للهجرة',
			'hv2_offers_subtitle'   => 'اختر المسار المناسب واطّلع على تفاصيله في صفحته.',
			'hv2_offers_btn'        => 'عرض الكل',
			'hv2_offers'            => array(
				array( 'tag' => 'الدراسة', 'title' => 'الدراسة في روسيا', 'text' => 'من البكالوريوس حتى الدكتوراه في الجامعات الحكومية والطبية.' ),
				array( 'tag' => 'الدراسة', 'title' => 'القبول المباشر', 'text' => 'الالتحاق مباشرة بالبرنامج الرئيسي للمتقدمين المؤهلين.' ),
				array( 'tag' => 'الدراسة', 'title' => 'السنة التحضيرية', 'text' => 'اللغة الروسية والمواد الأساسية قبل البرنامج الرئيسي.' ),
				array( 'tag' => 'التأشيرة', 'title' => 'التأشيرة الدراسية', 'text' => 'الدعوة وموعد السفارة والمتابعة حتى صدور التأشيرة.' ),
				array( 'tag' => 'الهجرة', 'title' => 'العمل في روسيا', 'text' => 'المسارات وشروط العمل في روسيا.' ),
				array( 'tag' => 'الهجرة', 'title' => 'الإقامة في روسيا', 'text' => 'دليل أنواع الإقامة وخطوات الحصول عليها.' ),
				array( 'tag' => 'الهجرة', 'title' => 'الجنسية الروسية', 'text' => 'شروط وخطوات الحصول على الجنسية عبر المسارات القانونية.' ),
			),
			'hv2_pay_title'         => 'ابدأ مسار الهجرة *بالدفع بالتقسيط*',
			'hv2_pay_subtitle'      => 'خطط للتكاليف بوضوح وبما يتناسب مع مراحل ملفك. يحدد المستشار الشروط الدقيقة أثناء الاستشارة.',
			'hv2_pay_cta'           => 'اطلب استشارة',
			'hv2_pay_items'         => array(
				array( 'title' => 'دفع على مراحل', 'text' => 'ادفع بما يتناسب مع تقدم مراحل ملفك.' ),
				array( 'title' => 'أقساط مناسبة', 'text' => 'تُحدد شروط التقسيط بعد مراجعة الملف في الاستشارة.' ),
				array( 'title' => 'إدارة التكاليف', 'text' => 'احصل على تقدير واضح للتكاليف قبل البدء.' ),
			),
			'hv2_cases_title'       => 'قصص النجاح',
			'hv2_cases_subtitle'    => 'نماذج لمسارات أُنجزت بمرافقة لايف روس.',
			'hv2_cases_btn'         => 'عرض الكل',
			'hv2_tm_title'          => 'آراء عملائنا',
			'hv2_tm_subtitle'       => 'تجارب من أنهوا مسارهم مع لايف روس.',
			'hv2_ig_title'          => 'مجتمعنا على *إنستغرام*',
			'hv2_ig_text'           => 'تابع القصص والأخبار والنصائح العملية حول الهجرة والدراسة في روسيا.',
			'hv2_ig_btn'            => 'تابعنا على إنستغرام',
			'hv2_app_title'         => 'تطبيق مجاني لتعلم اللغة',
			'hv2_app_text'          => 'ابدأ تعلم الروسية اليوم مع تطبيقنا المجاني.',
			'hv2_app_btn'           => 'تحميل التطبيق',
			'hv2_blog_title'        => 'أحدث مقالات الهجرة',
			'hv2_blog_btn'          => 'عرض الكل',
		),
	);
	return isset( $all[ $lang ] ) ? $all[ $lang ] : array();
}
