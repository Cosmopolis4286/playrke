// Tipos para la estructura del contenido de privacidad
export interface PrivacyListItem {
    text: string
}

export interface PrivacySection {
    title: string
    content: Array<{
        type: 'paragraph' | 'list'
        text?: string
        items?: PrivacyListItem[]
    }>
}

export interface PrivacyContent {
    title: string
    intro: string[]
    effectiveDate: string
    sections: PrivacySection[]
}

export const privacyContent: PrivacyContent = {
    title: 'POLÍTICA DE PRIVACIDAD',
    intro: [
        'Esta Política de Privacidad rige la manera en la que PlayRKE recopila, analiza, maneja, utiliza, mantiene, controla y procesa información suministrada por los usuarios. Por lo tanto, esta se aplica a todos los servicios, productos y/o aplicaciones en este sitio web, así como en nuestros subdominos correspondientes.',
        'Es posible que hagamos cambios periódicamente a esta Política de Privacidad. Sin embargo, le notificaremos estos cambios, publicando los términos modificados en nuestras plataformas. Por lo tanto, recomendamos revisar esta documentación con regularidad.'
    ],
    effectiveDate: 'Fecha de entrada en vigor: 23 de mayo de 2022.',
    sections: [
        {
            title: '1. PRIVACIDAD',
            content: [
                {
                    type: 'paragraph',
                    text: 'El sitio web www.playrke.com garantiza que la información personal que usted envía cuenta con la seguridad necesaria. El manejo de dicha información se refiere únicamente a los términos de utilización necesarios para la administración de la cuenta misma.'
                },
                {
                    type: 'paragraph',
                    text: 'Los datos de los usuarios no serán entregados a terceros, salvo que deba ser revelada en cumplimiento a una orden judicial o requerimientos legales. Sin embargo, también son requeridos para el envío de premios físicos u obsequios o para fines publicitarios, si el usuario brinda su consentimiento previo.'
                },
                {
                    type: 'paragraph',
                    text: 'Tenga en cuenta que esta Política de Privacidad será acordada entre usted y playrke.com. ("Nosotros", "Nos" o "Nuestro" "PlayRKE", según corresponda). Esta Política de Privacidad es una parte integral de los Términos y Condiciones de playrke.com.'
                }
            ]
        },
        {
            title: '2. INFORMACIÓN RECOGIDA',
            content: [
                {
                    type: 'paragraph',
                    text: 'En PlayRKE recibimos información de nuestros usuarios al momento de su ingreso, información que es de vital existencia al usar nuestra web, abrir una cuenta o utilizar nuestros servicios y/o aplicaciones. Le podemos solicitar al usuario información personal incluyendo, pero no limitado a: dirección de correo electrónico, nombres y apellidos, documento de identidad, nombre de usuario, estado o residencia, edad, sexo, número de celular, datos bancarios, preferencias en el uso de la web, comentarios relacionados con nuestros servicios, etc.'
                },
                {
                    type: 'paragraph',
                    text: 'No recopilamos información personal sobre usted sin su conocimiento. Los usuarios siempre pueden negarse a proporcionar información de identificación personal, pero a su vez, es probable que esto pueda limitar o restringir el acceso y uso de algunos de nuestros servicios. Esta información la guardamos en servidores ubicados en USA y en otros sitios de vez en cuando. Al usar nuestros servicios, estos servidores mantienen un registro de actividad único para usted, este recopila cierta información administrativa y de tráfico, que incluye: dirección IP de origen, fecha y hora de acceso, páginas web visitadas, uso del idioma, informes de errores de software. y tipo de navegador utilizado. Toda esta información es fundamental para la prestación y calidad en nuestros servicios.'
                }
            ]
        },
        {
            title: '3. MEDIOS Y PROCESAMIENTO DE DATOS',
            content: [
                {
                    type: 'paragraph',
                    text: 'Podemos recopilar automáticamente ciertos datos como se menciona anteriormente y recibir información personal sobre usted cuando proporcione dicha información, esto a través de los servicios, medios e interacciones de PlayRKE en todos sus sistemas. Además, podemos recibir información personal de proveedores de servicios y proveedores en línea, y de listas de clientes adquiridas legalmente de proveedores externos.'
                },
                {
                    type: 'paragraph',
                    text: 'No obstante, también podemos contratar los servicios de proveedores de servicios externos; para brindar soporte técnico, procesar sus transacciones en línea y preservar su cuenta. PlayRKE toma medidas para garantizar que nuestros acuerdos con proveedores de servicios externos y proveedores en línea protejan su privacidad. Por lo tanto, también tendremos acceso a cualquier información que proporcione a dichos proveedores y servicios de comercio electrónico de terceros, a su vez, manejaremos la información personal como se establece en esta Política de Privacidad a continuación.'
                }
            ]
        },
        {
            title: '4. USO DE LA INFORMACIÓN',
            content: [
                {
                    type: 'paragraph',
                    text: 'Recopilamos y utilizamos tus datos de información personal con los siguientes propósitos:'
                },
                {
                    type: 'list',
                    items: [
                        { text: 'Mejorar el servicio al cliente y responder a las solicitudes de manera eficiente.' },
                        { text: 'Realizar comprobaciones de seguridad e identificación necesarias.' },
                        { text: 'Optimizar nuestro sitio y la experiencia de los usuarios.' },
                        { text: 'Cumplir con ciertos requisitos comerciales.' },
                        { text: 'Procesar depósitos, retiros, transferencias, pago y envío de premios.' },
                        { text: 'Ayudarte a participar en promociones de terceros, empresas y/o cualquier otro propósito relacionado con la gestión de los servicios.' },
                        { text: 'Enviarte correos electrónicos sobre ofertas promocionales e informaciones sobre nuestros productos y servicios.' }
                    ]
                }
            ]
        },
        {
            title: '5. EXCLUSIONES DE LA REVELACIÓN DE INFORMACIÓN',
            content: [
                {
                    type: 'paragraph',
                    text: 'Es posible que revelemos tus datos personales si así lo exige la ley o si creemos necesaria dicha acción para:'
                },
                {
                    type: 'list',
                    items: [
                        { text: 'Cumplir con algún proceso legal que se nos asigne.' },
                        { text: 'Para enviar premios físicos a través de empresas de transporte.' },
                        { text: 'Proteger y defender nuestros derechos o propiedad.' },
                        { text: 'Resguardar la seguridad personal de los usuarios o del público.' }
                    ]
                },
                {
                    type: 'paragraph',
                    text: 'Sí, por determinación propia, descubrimos que has hecho trampa o has intentado defraudar a PlayRKE o nuestros servicios de cualquier forma, incluida, pero no exclusivamente, la manipulación de partidas, de códigos informáticos, de alteraciones de IP y de suplantación de identidad; serán penalizados según se estime.'
                }
            ]
        },
        {
            title: '6. ACCESO',
            content: [
                {
                    type: 'paragraph',
                    text: 'Usted puede optar por no recibir comunicaciones promocionales, eligiendo esto a través de la configuración de su cuenta o en un correo electrónico que reciba de nosotros, pero también en cualquier momento, enviando su petición a nuestro soporte técnico.'
                },
                {
                    type: 'paragraph',
                    text: 'Además, puede ponerse en contacto con nosotros si:'
                },
                {
                    type: 'list',
                    items: [
                        { text: 'Desea confirmar la precisión de los datos personales que hemos recopilado sobre usted.' },
                        { text: 'Requiere actualizar su información personal. Si se solicita, actualizaremos cualquier información que nos haya solicitado, esto en caso de que demuestre la necesidad de dichos cambios.' },
                        { text: 'Tiene alguna duda, reclamo o problema con respecto a nuestro uso de su información. Dejaremos constancia que dicha información no se debe utilizar con fines de marketing.' }
                    ]
                },
                {
                    type: 'paragraph',
                    text: 'Para evitar cualquier duda, nada en esta Política de privacidad nos impedirá retener su información personal cuando la ley así lo exija. Los datos personales que se obtengan como consecuencia de tu registro y/o actualización de datos, serán incorporados a un fichero titularidad de PlayRKE. Por lo cual, podrás ejercer los derechos de acceso, rectificación, cancelación y oposición, mediante una petición dirigida a nuestro correo electrónico soporte@playrke.com solicitando que no se te envíe información de marketing y excluyendo tu correo de nuestras listas. Para esto debes adjuntar copia de tu documento oficial de identidad, con el fin de verificar y garantizar tu identidad y proteger tus datos, evitando que terceros no autorizados accedan a los mismos. Al seleccionar la casilla de aceptación de nuestros Términos y Condiciones y Política de Privacidad, nos das autorización plena para que podamos utilizar tus datos para el envío de publicidad propia, y de aquellos servicios o productos que nuestros anunciantes, socios, inversionistas, o cualquier patrocinador que esté relacionado con playrke.com (PlayRKE). Por consiguiente, usted declara haber sido informado de las condiciones sobre protección de datos personales, aceptando y consintiendo el tratamiento que se realiza en la forma y para las finalidades indicadas en la presente política.'
                }
            ]
        },
        {
            title: '7. USO DE COOKIES',
            content: [
                {
                    type: 'paragraph',
                    text: 'En PlayRKE podemos usar cookies para mejorar la experiencia de los usuarios. Recibimos información de las cookies que son (pequeños archivos de texto que se almacenan en tu equipo) y tecnologías similares. Tanto nosotros como nuestros proveedores de servicios externos colocamos cookies de origen, que te permiten usar los servicios y nos ayudan a analizar y mejorar tu experiencia y nuestros sistemas. Las cookies que utilizamos se clasifican en:'
                },
                {
                    type: 'list',
                    items: [
                        { text: '<strong>Cookies estrictamente necesarias:</strong> estas cookies son obligatorias para que los servicios funcionen. Si eliges usar herramientas que desactiven estas cookies, puede que algunas partes de los servicios no funcionen correctamente.' },
                        { text: '<strong>Cookies funcionales:</strong> estas cookies nos ayudan a proporcionar funcionalidades mejoradas en los servicios, como recordar las preferencias de idioma. Desactivarlas podría afectar a alguna funcionalidad de los servicios.' },
                        { text: '<strong>Cookies de rendimiento:</strong> estas cookies permiten que nosotros o nuestros proveedores de análisis externos conozcamos cómo tú y otros usáis y participáis en los servicios, de modo que podamos entenderlos y mejorarlos.' }
                    ]
                },
                {
                    type: 'paragraph',
                    text: 'Los usuarios pueden elegir configurar su navegador web para rechazar las cookies o para avisarle cuando se envían las cookies. Si lo hacen, tengan en cuenta que algunos de nuestros servicios pueden no funcionar correctamente.'
                }
            ]
        },
        {
            title: '8. USO DE PROVEEDORES DE SERVICIOS ELECTRÓNICOS',
            content: [
                {
                    type: 'paragraph',
                    text: 'Para participar en nuestras salas de juego con dinero real, tendrá que enviar y recibir dinero de nosotros. Podemos utilizar sistemas de pago electrónico de terceros para procesar dichas transacciones financieras. Por lo tanto, tomamos medidas para garantizar que nuestros acuerdos con los sistemas de pago protejan su privacidad. Al aceptar esta Política de Privacidad, usted acepta expresamente el uso su información personal, necesaria para el procesamiento de transacciones, incluida, cuando sea requerida la transferencia de información fuera de su país.'
                }
            ]
        },
        {
            title: '9. SEGURIDAD Y PROTECCIÓN DE DATOS',
            content: [
                {
                    type: 'paragraph',
                    text: 'Somos plenamente conscientes de lo importante que es la seguridad de la información. Por lo tanto, adoptamos métodos y prácticas necesarias para la recopilación, almacenamiento y procesamiento de datos, protección contra el acceso no autorizado, alteración, divulgación o destrucción de su información personal, entre otros.'
                },
                {
                    type: 'paragraph',
                    text: 'El intercambio de datos confidenciales y privados entre PlayRKE con sus usuarios ocurre a través de un canal de comunicación seguro SSL/TLS, cifrado y protegido. Toda la información personal que recibimos directamente de usted se almacena en una base de datos encriptada y protegida con contraseña que reside en servidores externos, en una red segura detrás de un software de firewall activo de última generación.'
                },
                {
                    type: 'paragraph',
                    text: 'También implementamos medidas específicas para proteger la información de nuestros socios, anunciantes, agentes, afiliados y proveedores. Además, aplicamos controles para garantizar que empleen medidas de seguridad adecuadas, no obstante, estos son propiedad y están operados de forma independiente a nosotros, por lo tanto, no podemos garantizar completamente el uso y correcto manejo de datos.'
                }
            ]
        },
        {
            title: '10. CONSENTIMIENTO PARA LA REVISIÓN DE SEGURIDAD',
            content: [
                {
                    type: 'paragraph',
                    text: 'Nos reservamos el derecho de realizar revisiones de seguridad en cualquier momento, para validar los datos de registro proporcionados por usted y para verificar su uso de los servicios y sus transacciones financieras, esto por posibles incumplimientos de nuestros Términos y Condiciones y de la ley aplicable. Al usar nuestros servicios y por consiguiente, aceptar nuestros Términos y Condiciones, usted nos autoriza a usar su información personal y a exponer sus datos a terceros; con el fin de validar la información que proporciona durante el uso de nuestros servicios, esto incluye también cuando sea necesaria la transferencia de información fuera de su país. Las revisiones de seguridad pueden incluir, pero no se limitan a solicitar un informe de crédito y/o verificar la información que proporciona contra bases de datos de terceros. Igualmente, para facilitar estas revisiones de seguridad, acepta proporcionar la información o documentación que podamos solicitar.'
                }
            ]
        },
        {
            title: '11. PROTECCIÓN DE MENORES',
            content: [
                {
                    type: 'paragraph',
                    text: 'Nuestros servicios, concursos, sorteos, productos y demás aplicaciones no tienen como objetivo ni están dirigidos a personas menores de dieciocho (18) años (o la edad legal en su jurisdicción respectiva). PlayRKE no es responsable por el incumplimiento de esta normativa. Cualquier persona que nos proporcione su información a través de cualquier parte de nuestros servicios nos confirma que tiene dieciocho (18) años de edad (o la edad legal en su jurisdicción respectiva) o más. Sin embargo, si somos conscientes de que un menor ha intentado o enviado información personal a través de nuestros servicios; tomaremos las medidas necesarias para rechazar o eliminar toda la información que legalmente se nos permita de nuestros registros.'
                }
            ]
        },
        {
            title: '12. EXCEPCIÓN DE RESPONSABILIDAD',
            content: [
                {
                    type: 'paragraph',
                    text: 'Los servicios, productos y/o aplicaciones propiedad de PlayRKE operan tal como se ofrecen y cuando están disponibles, sin perjuicio de ninguna clase. Debido a la naturaleza cambiante de nuestra tecnología y servicios, no podemos garantizar ni afirmar que habrá una actuación sin errores en cuanto a la gestión de tus datos personales. No somos responsables bajo ningún caso de ningún daño punitivo indirecto, accidental o como consecuencia del uso o liberación de los datos personales.'
                }
            ]
        },
        {
            title: '13. OTROS SITIOS WEB',
            content: [
                {
                    type: 'paragraph',
                    text: 'Nuestro sitio web y demás aplicaciones pueden contener enlaces a otros sitios web, que están fuera de nuestro control y no están cubiertos por esta Política de Privacidad. Si accede a estos utilizando los enlaces proporcionados, los operadores de estos sitios podrán recopilar información sobre usted, que utilizarán de acuerdo con sus propias condiciones y política de privacidad, la cual puede diferir de la nuestra. No somos responsables, únicamente los operadores de estos sitios web serán los responsables, tanto del funcionamiento de los mismos, así como también, del manejo seguro de la información y demás consecuentes.'
                }
            ]
        },
        {
            title: '14. PRÁCTICAS DE TERCEROS',
            content: [
                {
                    type: 'paragraph',
                    text: 'No podemos garantizar la protección de ninguna información que proporcione una aplicación o un sitio en línea de un tercero; que se vincule hacia nuestros los servicios o cualquier información recopilada sin consentimiento previo (si corresponde). Estos sitios, programas o aplicaciones de las partes son propiedad y están operados de forma independiente a nosotros. Cualquier información recopilada por estos terceros se rige por su política de privacidad, si corresponde, de dichos terceros.'
                }
            ]
        },
        {
            title: '15. TRANSFERENCIA',
            content: [
                {
                    type: 'paragraph',
                    text: 'Si en el futuro somos adquiridos por una tercera parte o nos fusionamos con ella, transferimos sustancialmente todo o parte de dichos bienes a una tercera parte, o vendemos todo o parte de nuestros bienes; tendremos el derecho de compartir la "Información Personal" y cualquier otro tipo de información que nos hayas facilitado, a través de los servicios con socios potenciales y futuros de la empresa en fusión. Al aceptar estas condiciones generales otorgas tu total aceptación y/o consentimiento a la transferencia de dicha información.'
                }
            ]
        },
        {
            title: '16. CONSENTIMIENTO A LA POLÍTICA DE PRIVACIDAD',
            content: [
                {
                    type: 'paragraph',
                    text: 'Al utilizar el sitio web PlayRKE usted expresa su aceptación de esta política completa y exclusiva y reemplaza cualquier versión anterior. Si no está de acuerdo, por favor no utilice nuestros servicios. Esta Política de Privacidad debe leerse junto con nuestros Términos y Condiciones, y cualquier otro término adicional aplicable publicado en nuestro sitio web y demás plataformas. Cuando haces clic en "Acepto" y/o "Estoy de acuerdo" durante el proceso de registro o cuando haces uso continuado de los servicios, productos y/o aplicaciones de PlayRKE será considerado su aceptación o consentimiento de esta Política de Privacidad.'
                }
            ]
        }
    ]
}
