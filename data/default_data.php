<?php
// ============================================================
//  default_data.php — Datos por defecto de inicio en MySQL (Estilo MyBB)
// ============================================================

return [
    'settings' => [
        'partner_one'       => 'Ella',
        'partner_two'       => 'Él',
        'start_date'        => '',
        'proposal_accepted' => '0',
        'site_lang'         => 'es',
        'date_locale'       => 'es-ES',
    ],

    'ui_media' => [
        'cover_image_url'  => 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?auto=format&fit=crop&w=800&q=80',
        'second_image_url' => 'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?auto=format&fit=crop&w=800&q=80',
        'bg_music_url'     => 'https://cdn.pixabay.com/audio/2022/05/27/audio_1808fbf07a.mp3'
    ],

    'letter_paragraphs' => [
        "Mi vida,",
        "Si me hubieran dicho tiempo atrás que me enamoraría de la forma en que lo he hecho de ti, jamás lo habría creído. Contigo descubrí que el amor no se trata solo de coincidir, sino de encontrar a esa persona que hace que cualquier día común se sienta como el mejor regalo.",
        "Amo la paz que me da tu abrazo, la magia con la que llenas cada espacio y la forma tan única en la que me miras. Contigo aprendí que mi lugar favorito en el mundo no es un sitio geográfico... es estar a tu lado.",
        "Hay algo que todavía no te he confesado. Algo que mis ojos llevan tiempo intentando decirte cada vez que te miran, aunque mis palabras nunca hayan sabido cómo hacerlo.",
        "Porque contigo pasó algo diferente. Sin pedir permiso, te fuiste colando en mis pensamientos y en mis mejores días. Y entonces entendí que, desde el mismo día en que te vi, había una pregunta que no dejaba de rondarme la cabeza.",
        "Y hoy, por fin, quiero hacértela..."
    ],

    'reasons' => [
        "Amo cómo se iluminan tus ojos cuando sonríes.",
        "La calma absoluta que siento cuando me abrazas.",
        "Cómo logras transformar un día ordinario en algo especial.",
        "Tu forma única de escucharme y entender mi mundo.",
        "Tu bondad y la ternura con la que tratas a los demás.",
        "Que a tu lado puedo ser 100% yo mismo.",
        "La forma en que me miras incluso cuando no me doy cuenta."
    ],

    'timeline_chapters' => [
        [
            'chapter_label' => 'Capítulo 1',
            'title'         => 'El Primer Encuentro',
            'description'   => 'El día en que cruzamos miradas por primera vez y el mundo pareció detenerse un instante.'
        ],
        [
            'chapter_label' => 'Capítulo 2',
            'title'         => 'Nuestra Primera Risa Juntos',
            'description'   => 'Ese momento en el que me di cuenta de que tu risa se convertiría en mi sonido favorito.'
        ],
        [
            'chapter_label' => 'Capítulo 3',
            'title'         => 'Un Viaje Inolvidable',
            'description'   => 'Cada paseo y aventura donde entendí que no importa el lugar, sino la compañía.'
        ]
    ],

    'wishes' => [
        [
            'icon'        => '✈️',
            'label'       => 'Un Viaje Juntos',
            'secret_text' => 'Descubrir un nuevo país agarrados de la mano.'
        ],
        [
            'icon'        => '☕',
            'label'       => 'Mañanas Pacíficas',
            'secret_text' => 'Prepararte el café cada mañana con una sonrisa.'
        ],
        [
            'icon'        => '🏡',
            'label'       => 'Nuestro Espacio',
            'secret_text' => 'Construir un lugar donde siempre reine la paz.'
        ],
        [
            'icon'        => '🌟',
            'label'       => 'Siempre Apoyarte',
            'secret_text' => 'Estar a tu lado en cada sueño que decidas emprender.'
        ]
    ]
];

