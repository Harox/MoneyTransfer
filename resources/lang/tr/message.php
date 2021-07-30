<?php

$getCompanyName = getCompanyName();

return [
    'sidebar'              => [
        'dashboard'    => 'gösterge paneli',
        'users'        => 'Kullanıcılar',
        'transactions' => 'işlemler',
        'settings'     => 'Ayarlar',

    ],
    'footer'               => [
        'follow-us'      => 'Bizi takip et',
        'related-link'   => 'İlgili Bağlantılar',
        'categories'     => 'Kategoriler',
        'language'       => 'Dil',
        'copyright'      => 'telif hakkı',
        'copyright-text' => 'Tüm hakları Saklıdır',

    ],
    '2sa'                  => [
        'title-short-text'             => 'geri',
        'title-text'                   => '2-Faktör Doğrulama',
        'extra-step'                   => 'Bu ekstra adım, gerçekten oturum açmaya çalıştığınızı gösterir.',
        'extra-step-settings-verify'   => 'Bu ekstra adım, gerçekten doğrulamaya çalıştığınızı gösteriyor.',
        'confirm-message'              => '6 basamaklı bir kimlik doğrulama kodu içeren bir kısa mesaj gönderildi.',
        'confirm-message-verification' => '6 basamaklı doğrulama kodunu içeren bir kısa mesaj gönderildi.',
        'remember-me-checkbox'         => 'Beni bu tarayıcıda hatırla',
        'verify'                       => 'DOĞRULAYIN',

    ],
    'personal-id'          => [
        'title'                 => 'kimlik doğrulama',
        'identity-type'         => 'kimlik tipi',
        'select-type'           => 'Türü Seç',
        'driving-license'       => 'Sürücü ehliyeti',
        'passport'              => 'Pasaport',
        'national-id'           => 'Ulusal Kimliği',
        'identity-number'       => 'Kimlik Numarası',
        'upload-identity-proof' => 'Kimlik Kanıtını Yükle',

    ],
    'personal-address'     => [
        'title'                => 'Adres Doğrulama',
        'upload-address-proof' => 'Adres Kanıtını Yükle',

    ],
    'google2fa'            => [
        'title-text'     => 'Google İki Faktörlü Kimlik Doğrulama (2FA)',
        'subheader-text' => 'Google Şifrematik Uygulaması ile QR Kodunu tarayın.',
        'setup-a'        => 'Devam etmeden önce Google Şifrematik uygulamanızı kurun.',
        'setup-b'        => 'Aksini doğrulayamayacaksınız.',
        'proceed'        => 'Doğrulamaya Devam Et',
        'otp-title-text' => 'Tek Kullanımlık Şifre (OTP)',
        'otp-input'      => 'Google Şifrematik Uygulamasından 6 haneli OTP\'yi girin',

    ],
    'form'                 => [

        'button'                   => [
            'sign-up' => 'Kaydol',
            'login'   => 'Oturum aç',

        ],
        'forget-password-form'     => 'Parolanızı mı unuttunuz',
        'reset-password'           => 'Şifreyi yenile',
        'yes'                      => 'Evet',
        'no'                       => 'Yok hayır',
        'add'                      => 'Yeni ekle',
        'category'                 => 'Kategori',
        'unit'                     => 'Birimler',
        'category_create'          => 'Kategori oluştur',
        'category_edit'            => 'Kategoriyi Düzenle',
        'location_create'          => 'Yer oluştur',
        'location_edit'            => 'Konumunu düzenle',
        'location_name'            => 'Yer ismi',
        'location_code'            => 'Konum kodu',
        'delivery_address'         => 'Teslim adresi',
        'default_loc'              => 'Varsayılan konum',
        'phone_one'                => 'Bir telefon',
        'phone_two'                => 'İki telefon',
        'fax'                      => 'Faks',
        'email'                    => 'E-posta',
        'username'                 => 'Kullanıcı adı',
        'contact'                  => 'Temas',
        'item_create'              => 'Öğe oluştur',
        'unit_create'              => 'Birim oluştur',
        'unit_edit'                => 'Birimi Düzenle',
        'item_id'                  => 'Öğe kimliği',
        'item_name'                => 'Öğe adı',
        'quantity'                 => 'miktar',
        'item_des'                 => 'Ürün Açıklaması',
        'picture'                  => 'Resim',
        'location'                 => 'yer',
        'add_stock'                => 'Stok ekle',
        'select_one'               => 'Birini seç',
        'memo'                     => 'not',
        'close'                    => 'Kapat',
        'remove_stock'             => 'Stokları Kaldır',
        'move_stock'               => 'Stoku Taşı',
        'location_from'            => 'Yer',
        'location_to'              => 'Yer',
        'item_edit'                => 'Ögeyi düzenle',
        'copy'                     => 'kopya',
        'store_in'                 => 'Depolamak',
        'order_items'              => 'Sipariş öğeleri',
        'delivery_from'            => 'Konumdan Teslim',
        'user_role_create'         => 'Kullanıcı Rolü Oluştur',
        'permission'               => 'izin',
        'section_name'             => 'Bölüm adı',
        'areas'                    => 'alanlar',
        'Add'                      => 'Eklemek',
        'Edit'                     => 'Düzenle',
        'Delete'                   => 'silmek',
        'name'                     => 'isim',
        'request_to'               => 'İstenilen',
        'request_from'             => 'İstenen',
        'full_name'                => 'Ad Soyad',
        'password'                 => 'Parola',
        'old_password'             => 'eski şifre',
        'set_password'             => 'Şifreyi belirle',
        'new_password'             => 'Yeni Şifre',
        'update_password'          => 'Şifre güncelle',
        'confirm_password'         => 'Şifreyi Onayla',
        're_password'              => 'Şifreyi tekrar girin',
        'change_password'          => 'Şifre değiştir',
        'settings'                 => 'Ayarlar',
        'change_password_form'     => 'Şifre Formunu Değiştir',
        'user_create_form'         => 'Kullanıcı oluştur',
        'user_update_form'         => 'Kullanıcıyı Güncelle',
        'submit'                   => 'Gönder',
        'update'                   => 'Güncelleştirme',
        'cancel'                   => 'İptal etmek',
        'sign_out'                 => 'Oturumu Kapat',
        'delete'                   => 'silmek',
        'company_create'           => 'Şirket oluştur',
        'company'                  => 'şirket',
        'db_host'                  => 'evsahibi',
        'db_user'                  => 'Veritabanı kullanıcısı',
        'db_password'              => 'Veritabanı Şifresi',
        'db_name'                  => 'Veri tabanı ismi',
        'new_company_password'     => 'Yeni script Yönetici Şifresi',
        'pdf'                      => 'PDF',
        'customer'                 => 'Müşteri',
        'customer_branch'          => 'Müşteri şube',
        'payment_type'             => 'Ödeme türü',
        'from_location'            => 'yer',
        'add_item'                 => 'Öğe eklemek',
        'sales_invoice_items'      => 'Satış Faturası Kalemleri',
        'purchase_invoice_items'   => 'Satınalma faturası öğeleri',
        'supplier'                 => 'satıcı',
        'order_item'               => 'Sipariş öğesi',
        'order_date'               => 'Sipariş Ver',
        'item_tax_type'            => 'Vergi Türü',
        'currency'                 => 'Para birimi',
        'sales_type'               => 'Satış Türü',
        'price'                    => 'Fiyat',
        'supplier_unit_of_messure' => 'Tedarikçiler Ölçü Birimi',
        'conversion_factor'        => 'Dönüşüm Faktörü (UOM\'umuza)',
        'supplier_description'     => 'Tedarikçi Kodu veya Açıklaması',
        'next'                     => 'Sonraki',
        'add_branch'               => 'Şube ekle',
        'payment_term'             => 'Ödeme koşulu',
        'site_name'                => 'Site adı',
        'site_short_name'          => 'Site kısa adı',
        'source'                   => 'Kaynak',
        'destination'              => 'Hedef',
        'stock_move'               => 'Stok Aktarımı',
        'after'                    => 'Sonra',
        'status'                   => 'durum',
        'date'                     => 'tarih',
        'qty'                      => 'Adet',
        'terms'                    => 'terim',
        'add_new_customer'         => 'Yeni müşteri ekle',
        'add_new_order'            => 'Yeni Sipariş Ekle',
        'add_new_invoice'          => 'Yeni Fatura Ekle',
        'group_name'               => 'Grup ismi',
        'edit'                     => 'Düzenle',
        'title'                    => 'Başlık',
        'description'              => 'Açıklama',
        'reminder'                 => 'Hatırlatma Tarihi',

    ],
    'home'                 => [

        'title-bar'       => [
            'home'      => 'Ev',
            'send'      => 'göndermek',
            'request'   => 'İstek',
            'developer' => 'Geliştirici',
            'login'     => 'Oturum aç',
            'register'  => 'Kayıt olmak',
            'logout'    => 'Çıkış Yap',
            'dashboard' => 'gösterge paneli',

        ],
        'banner'          => [
            'title'      => 'Basit para transferi için sevdiklerinize',
            'sub-title1' => 'Basit: Entegre Ol',
            'sub-title2' => 'Çoklu Cüzdan',
            'sub-title3' => 'Gelişmiş Güvenlik',

        ],
        'choose-us'       => [
            'title'      => 'Neden bizi seçmelisiniz',
            'sub-title1' => 'Biz banka değiliz. Bizimle düşük ücretler ve gerçek zamanlı döviz kurları alırsınız.',
            'sub-title2' => 'Paranızı ailenize ve arkadaşlarınıza anında ulaştırın, sadece bir e-posta adresine ihtiyacınız var.',
            'sub-title3' => 'Para transferi, para çekme ve döviz bozdurma ücretleri - düşük ücrete tabidir.',

        ],
        'payment-gateway' => [
            'title' => 'Ödeme İşlemcileri',

        ],
        'services'        => [
            't1' => 'Ödeme API\'sı',
            's1' => 'Müşterileri yönetecek ' . $getCompanyName . ' Sorunsuz API arayüzümüzü web sitenize entegre ederek deneyim kazanın.',
            't2' => 'Çevrimiçi Ödemeler',
            's2' => 'Kredi, banka veya banka hesabı ne olursa olsun, sizin tarafınızdan ödeme yapabilirsiniz.',
            't3' => 'Döviz değişimi',
            's3' => 'Varsayılan para bir başkasına kolayca değiştirebilirsiniz.',
            't4' => 'Ödeme talebi',
            's4' => 'Bu sistemlerle artık herhangi bir ülkeden herhangi bir ülkeye ödeme talebinde bulunabilirsiniz.',
            't5' => 'Kupon sistemi',
            's5' => 'Kendi markalı veya yetenekli kuponlarınızı verin ve yönetin',
            't6' => 'Dolandırıcılık Tespiti',
            's6' => 'Hesabınızı daha güvenli ve güvenilir tutmaya yardımcı olduğumuz anlamına gelir. Güvenli çevrimiçi ödemelerin tadını çıkarın.',

        ],
        'how-work'        => [
            'title'      => 'Nasıl çalışır',
            'sub-title1' => 'Öncelikle hesabınıza para yatırın.',
            'sub-title2' => 'M-cüzdan göndermek ve göndermek istediğiniz tutarı belirleyin.',
            'sub-title3' => 'İsterseniz kısa bir notla e-posta adresini not edin.',
            'sub-title4' => 'Para göndermek için üzerine tıklayın.',
            'sub-title5' => 'Paranızı da değiştirebilirsiniz.',

        ],
    ],
    'send-money'           => [

        'banner'    => [
            'title'     => 'Size Uygun Şekilde Para Gönder',
            'sub-title' => 'Hızlı ve kolay bir şekilde para gönderip alın ya da hediye olarak bir kupon verin.',
            'sign-up'   => 'Üye Ol ' . $getCompanyName,
            'login'     => 'Şimdi giriş yap',

        ],
        'section-a' => [
            'title'         => 'Çok Az Para Birimi Birkaç Dakika İçinde Dünyada Para Göndermek Sadece Birkaçında
                            Tıklanma.',

            'sub-section-1' => [
                'title'     => 'Kayıt Hesabı',
                'sub-title' => 'İlk önce bir kayıt kullanıcısı olun, ardından hesabınıza giriş yapın ve kartınızı veya bankanızı girin
                            sizin için gerekli olan detaylar.',

            ],
            'sub-section-2' => [
                'title'     => 'Alıcınızı Seçin',
                'sub-title' => 'Başkalarıyla paylaşmayacak ve güvenliğini koruyacak alıcı e-posta adresinizi girin.
                            güvenli bir şekilde göndermek için para bir miktar ekleyin.',

            ],
            'sub-section-3' => [
                'title'     => 'Para göndermek',
                'sub-title' => 'Para gönderildikten sonra, alıcı para gönderildiğinde e-posta yoluyla bilgilendirilecek
                            hesaplarına aktarıldı.',

            ],
        ],
        'section-b' => [
            'title'     => 'Bir saniye içinde para gönderin.',
            'sub-title' => 'Bir e-posta adresine sahip olan herkes, bir hesabın olup olmadığını ödeme isteği gönderebilir / alabilir. Kredi kartı veya banka hesabıyla ödeme yapabilirler',

        ],
        'section-c' => [
            'title'     => 'Anında Kullanarak Herkese, Her Yere Para Gönder ' . $getCompanyName . ' sistem',
            'sub-title' => 'Global olarak arkadaşlarınıza ve ailenize fon transferi yapın ' . $getCompanyName . ' mobil uygulama, banka hesabı veya diğer ödeme ağ geçitleri. Alıcının hesabı olup olmadığına bakılmaksızın doğrudan hesabınıza para yatırılır. Farklı para birimlerine sahip farklı türde Ödeme Ağ Geçidi üzerinden para gönderebilir / talep edebilirsiniz.',

        ],
        'section-d' => [
            'title'   => 'Daha hızlı, Daha basit, Daha güvenli - Bugün sevdiğiniz kişilere para gönderin.',
            'sign-up' => 'Üye Ol ' . $getCompanyName,
        ],
        'section-e' => [
            'title'     => 'Para göndermeye başla.',
            'sub-title' => 'Şimdi, nakit paraya sahip olmakta sorun yok. Kartından veya bankadan herkes para gönderebilir
                            hesap, paypal bakiyesi veya diğer ödeme yolu. Basit bir e-posta ile bilgilendirileceksiniz.',

        ],
    ],
    'request-money'        => [

        'banner'    => [
            'title'          => 'Dünyanın Her Yerinden Para İsteyin ' . $getCompanyName,
            'sub-title'      => 'İnsanlara para iadesi göndermesi için bir hatırlatma yapın.',
            'sign-up'        => 'Üye Ol ' . $getCompanyName,
            'already-signed' => 'Zaten kaydoldu mu?',
            'login'          => 'oturum aç',
            'request-money'  => 'para talep etmek.',

        ],
        'section-a' => [
            'title'         => 'Kullanıcı Dostu Para İsteği Sistemi.',
            'sub-title'     => 'Para istemek, borçlu olduğunuz para istemek için etkili ve kibar bir yoldur. kullanım ' . $getCompanyName . ' para göndermek, en yakın ve en sevdiklerinizden para aktarmak için kullanılır.',

            'sub-section-1' => [
                'title'     => 'Kayıt Hesabı',
                'sub-title' => 'İlk önce bir kayıt kullanıcısı olun, ardından hesabınıza giriş yapın ve kartınızı veya bankanızı girin
                            Para talebinde bulunmanız için gerekli olan detaylar.',

            ],
            'sub-section-2' => [
                'title'     => 'Alıcınızı Seçin',
                'sub-title' => 'Başkalarıyla paylaşmayacak ve güvende kalmayacak şekilde alıcı e-posta adresinizi girin.
                            güvenli bir şekilde göndermek için para bir miktar ekleyin.',

            ],
            'sub-section-3' => [
                'title'     => 'Para İste',
                'sub-title' => 'Para talebinde bulunulduktan sonra, alıcı parayı e-posta yoluyla bildirecektir.
                            Onların hesabından transfer edildi.',

            ],
        ],
        'section-b' => [
            'title'     => 'Cep Telefonuyla Para Gönderebilir',
            'sub-title' => 'Şimdi, nakit paraya sahip olmakta sorun yok. Kartından veya bankadan herkes para gönderebilir
                            hesap, paypal bakiyesi veya diğer ödeme yolu. Basit bir e-posta ile bilgilendirileceksiniz.',

        ],
        'section-c' => [
            'title'     => 'Kullan ' . $getCompanyName . ' Kolayca Para Talebi İçin Mobile App.',
            'sub-title' => 'E-posta adresine sahip olan herkes, bir hesabına sahip olup olmadıklarına bakılmaksızın bir ödeme isteği alabilirler.
                            değil. Paypal, şerit, 2 çek ve daha birçok ödeme yolu ile ödeme yapabilirler.',

        ],
        'section-d' => [
            'title'     => 'Anında Kullanarak Herkese, Her Yere Para Talep Edin ' . $getCompanyName . ' sistem',
            'sub-title' => 'Global olarak arkadaşlarınıza ve ailenize fon transferi yapın ' . $getCompanyName . ' mobil uygulama, banka hesabı veya başkaları ödeme ağ geçidi. Fonlar, alıcının hesabı olup olmadığına doğrudan hesabınıza geçer. Farklı para birimlerine sahip farklı türde Ödeme Ağ Geçidi üzerinden para gönderebilir / talep edebilirsiniz.',

        ],
        'section-e' => [
            'title'   => 'Daha hızlı, Daha basit, Daha güvenli - Bugün sevdiğiniz kişilere para gönderin.',
            'sign-up' => 'Üye Ol ' . $getCompanyName,

        ],
    ],
    'login'                => [
        'title'           => 'Oturum aç',
        'form-title'      => 'Oturum aç',
        'email'           => 'E-posta',
        'phone'           => 'Telefon',
        'email_or_phone'  => 'Eposta ya da telefon',
        'password'        => 'Parola',
        'forget-password' => 'Şifreyi unut?',
        'no-account'      => 'Hesabın yok mu?',
        'sign-up-here'    => 'Buradan kayıt olun',

    ],
    'registration'         => [
        'title'                => 'kayıt',
        'form-title'           => 'Yeni kullanıcı oluştur',
        'first-name'           => 'İsim',
        'last-name'            => 'Soyadı',
        'email'                => 'E-posta',
        'phone'                => 'Telefon',
        'password'             => 'Parola',
        'confirm-password'     => 'Şifreyi Onayla',
        'terms'                => 'Kaydol\'u tıklayarak Şartlarımızı, Veri Politikamızı ve Çerez Politikamızı kabul etmiş olursunuz.',
        'new-account-question' => 'Zaten hesabınız var mı?',
        'sign-here'            => 'Oturum açın',
        'type-title'           => 'tip',
        'type-user'            => 'kullanıcı',
        'type-merchant'        => 'tüccar',
        'select-user-type'     => 'Kullanıcı türünü seçin',

    ],
    'dashboard'            => [
        'mail-not-sent' => 'ancak postalar gönderilemedi',
        'nav-menu'      => [
            'dashboard'    => 'gösterge paneli',
            'transactions' => 'işlemler',
            'send-req'     => 'İstek gönder',
            'send-to-bank' => 'Bankaya Gönder',
            'merchants'    => 'tüccarlar',
            'disputes'     => 'İhtilaflar',
            'settings'     => 'Ayarlar',
            'tickets'      => 'Biletler',
            'logout'       => 'Çıkış Yap',
            'payout'       => 'Ödeme',
            'exchange'     => 'Değiş tokuş',

        ],
        'left-table'    => [
            'title'            => 'Son Etkinlik',
            'date'             => 'tarih',
            'description'      => 'Açıklama',
            'status'           => 'durum',
            'currency'         => 'Para birimi',
            'amount'           => 'Miktar',
            'view-all'         => 'Hepsini gör',
            'no-transaction'   => 'İşlem bulunamadı!',
            'details'          => 'ayrıntılar',
            'fee'              => 'ücret',
            'total'            => 'Genel Toplam',
            'transaction-id'   => 'İşlem Kimliği',
            'transaction-date' => 'İşlem günü',

            'deposit'          => [
                'deposited-to'     => 'Mevduat',
                'payment-method'   => 'Ödeme şekli',
                'deposited-amount' => 'Mevduat Tutarı',
                'deposited-via'    => 'Via Depozited',

            ],
            'withdrawal'       => [
                'withdrawan-with'   => 'İle ödeme',
                'withdrawan-amount' => 'Ödeme Tutarı',

            ],
            'transferred'      => [
                'paid-with'          => 'Ücretli',
                'transferred-amount' => 'Aktarılan Tutar',
                'email'              => 'E-posta',
                'note'               => 'Not',
                'paid-to'            => 'Ödenen',
                'transferred-to'     => 'Transfer edildi',
                'phone'              => 'Telefon'
            ],
            'bank-transfer'    => [
                'bank-details'        => 'Banka detayları',
                'bank-name'           => 'Banka adı',
                'bank-branch-name'    => 'Şube adı',
                'bank-account-name'   => 'Hesap adı',
                'bank-account-number' => 'Hesap numarası',
                'transferred-with'    => 'İle aktarıldı',
                'transferred-amount'  => 'Banka Tarafından Aktarılan Tutar',

            ],
            'received'         => [
                'paid-by'         => 'Ücretli',
                'received-from'   => 'Alınan',
                'received-amount' => 'Alınan miktar',

            ],
            'exchange-from'    => [
                'from-wallet'          => 'Cüzdandan',
                'exchange-from-amount' => 'Döviz Tutarı',
                'exchange-from-title'  => 'Değişim',
                'exchange-to-title'    => 'Değişim',

            ],
            'exchange-to'      => [
                'to-wallet' => 'Cüzdan\'a',

            ],
            'request-to'       => [
                'accept' => 'Kabul etmek',

            ],
            'payment-Sent'     => [
                'payment-amount' => 'Ödeme miktarı',

            ],
        ],
        'right-table'   => [
            'title'                => 'cüzdan',
            'no-wallet'            => 'Cüzdan bulunamadı!',
            'default-wallet-label' => 'Varsayılan',
            'crypto-send'          => 'Gönder',
            'crypto-receive'       => 'Teslim almak',
        ],
        'button'        => [
            'deposit'         => 'Depozito',
            'withdraw'        => 'Ödeme',
            'payout'          => 'Ödeme',
            'exchange'        => 'Değiş tokuş',
            'submit'          => 'Gönder',
            'send-money'      => 'Para göndermek',
            'send-request'    => 'İstek gönder',
            'create'          => 'yaratmak',
            'activate'        => 'etkinleştirmek',
            'new-merchant'    => 'Yeni Satıcı',
            'details'         => 'ayrıntılar',
            'change-picture'  => 'Resmi değiştir',
            'change-password' => 'Şifre değiştir',
            'new-ticket'      => 'Yeni Bilet',
            'next'            => 'Sonraki',
            'back'            => 'Geri',
            'confirm'         => 'Onaylamak',
            'select-one'      => 'Birini seç',
            'update'          => 'Güncelleştirme',
            'filter'          => 'filtre',

        ],
        'deposit'       => [
            'title'                                       => 'Depozito',
            'deposit-via'                                 => 'Para yatırma',
            'amount'                                      => 'Miktar',
            'currency'                                    => 'Para birimi',
            'payment-method'                              => 'Ödeme şekli',
            'no-payment-method'                           => 'Ödeme Şekli Bulunamadı!',
            'fees-limit-payment-method-settings-inactive' => 'Ücret Limiti ve Ödeme Yöntemi ayarlarının ikisi de etkin değildir',
            'total-fee'                                   => 'Toplamda:',
            'total-fee-admin'                             => 'Genel Toplam:',
            'fee'                                         => 'ücret',
            'deposit-amount'                              => 'Para Yatırma Tutarı',
            'completed-success'                           => 'Para Yatırma Başarıyla Tamamlandı',
            'success'                                     => 'başarı',
            'deposit-again'                               => 'Tekrar Para Yatırma',

            'deposit-stripe-form'                         => [
                'title'   => 'Stripe ile para yatırma',
                'card-no' => 'Kart numarası',
                'mm-yy'   => 'AA / YY',
                'cvc'     => 'CVC',

            ],
            'select-bank'                                 => 'Banka Seç',
            'payment-references'                          => [
                'user-payment-reference' => 'Kullanıcı Ödeme Referansı',
            ],
        ],
        'payout'        => [

            'menu'           => [
                'payouts'        => 'Ödemeler',
                'payout-setting' => 'Ödeme Ayarı',
                'new-payout'     => 'Yeni Ödeme',

            ],
            'list'           => [
                'method'      => 'Yöntem',
                'method-info' => 'Yöntem Bilgisi',
                'charge'      => 'Şarj etmek',
                'amount'      => 'Miktar',
                'currency'    => 'Para birimi',
                'status'      => 'durum',
                'date'        => 'tarih',
                'not-found'   => 'Veri bulunamadı !',
                'fee'         => 'ücret',

            ],
            'payout-setting' => [
                'add-setting' => 'Ayar ekle',
                'payout-type' => 'Ödeme Türü',
                'account'     => 'hesap',
                'action'      => 'Aksiyon',

                'modal'       => [
                    'title'                        => 'Ödeme Ayarı Ekle',
                    'payout-type'                  => 'Ödeme Türü',
                    'email'                        => 'E-posta',
                    'bank-account-holder-name'     => 'Banka Hesap Sahibi\'nin adı',
                    'branch-name'                  => 'Şube adı',
                    'account-number'               => 'Banka Hesap Numarası / IBAN',
                    'branch-city'                  => 'Şube Şehri',
                    'swift-code'                   => 'Swift kodu',
                    'branch-address'               => 'Şube adresi',
                    'bank-name'                    => 'Banka adı',
                    'attached-file'                => 'Ekli dosya',
                    'country'                      => 'ülke',
                    'perfect-money-account-number' => 'Mükemmel Para Hesabı Numarası',
                    'payeer-account-number'        => 'Alacaklı Hesabı Numarası',

                ],
            ],
            'new-payout'     => [
                'title'          => 'Ödeme',
                'payment-method' => 'Ödeme şekli',
                'currency'       => 'Para birimi',
                'amount'         => 'Miktar',
                'bank-info'      => 'Banka Hesap Bilgileri',
                'withdraw-via'   => 'Üzerinden para ödemek üzeresiniz',
                'success'        => 'başarı',
                'payout-success' => 'Ödeme başarıyla tamamlandı',
                'payout-again'   => 'Tekrar Ödeme',

            ],
        ],
        'confirmation'  => [
            'details' => 'ayrıntılar',
            'amount'  => 'Miktar',
            'fee'     => 'ücret',
            'total'   => 'Genel Toplam',

        ],
        'transaction'   => [
            'date-range'      => 'Bir tarih aralığı seç',
            'all-trans-type'  => 'Tüm İşlem Türü',
            'payment-sent'    => 'Ödeme gönderildi',
            'payment-receive' => 'Ödeme alındı',
            'payment-req'     => 'Ödeme talebi',
            'exchanges'       => 'Değişimleri',
            'all-status'      => 'Tüm durum',
            'all-currency'    => 'Tüm para birimi',
            'success'         => 'başarı',
            'pending'         => 'kadar',
            'blocked'         => 'İptal edildi',
            'refund'          => 'Geri Ödendi',
            'open-dispute'    => 'Açık anlaşmazlık',

        ],
        'exchange'      => [

            'left-top'    => [
                'title'           => 'Döviz kuru',
                'select-wallet'   => 'Cüzdan\'ı seçin',
                'amount-exchange' => 'Döviz Tutarı',
                'give-amount'     => 'Vereceksin',
                'get-amount'      => 'Alacaksın',
                'balance'         => 'Denge',
                'from-wallet'     => 'Cüzdandan',
                'to-wallet'       => 'Cüzdan\'a',
                'base-wallet'     => 'Cüzdandan',
                'other-wallet'    => 'Cüzdan\'a',
                'type'            => 'Exchange Türü',
                'type-text'       => 'Temel para birimi:',
                'to-other'        => 'Diğer Para Birime',
                'to-base'         => 'Temel Para Birimi',

            ],
            'left-bottom' => [
                'title'            => 'Döviz Kuru (Baz Para Birimi)',
                'exchange-to-base' => 'Üssün değişimi',
                'wallet'           => 'Cüzdan',

            ],
            'right'       => [
                'title' => 'Döviz kuru',

            ],
            'confirm'     => [
                'title'                => 'Döviz Para',
                'exchanging'           => 'alıp verme',
                'of'                   => 'arasında',
                'equivalent-to'        => 'Eşittir',
                'exchange-rate'        => 'Döviz kuru',
                'amount'               => 'Döviz Tutarı',
                'has-exchanged-to'     => 'değişti',
                'exchange-money-again' => 'Tekrar para değişimi',

            ],
        ],
        'send-request'  => [

            'menu'         => [
                'send'    => 'göndermek',
                'request' => 'İstek',

            ],
            'send'         => [
                'title'        => 'Para göndermek',

                'confirmation' => [
                    'title'              => 'Para göndermek',
                    'send-to'            => 'Para gönderiyorsun',
                    'transfer-amount'    => 'Transfer miktarı',
                    'money-send'         => 'Para başarıyla aktarıldı',
                    'bank-send'          => 'Bankaya Para Aktarımı Başarıyla Taşındı',
                    'send-again'         => 'Tekrar Para Gönderin',
                    'send-to-bank-again' => 'Banka\'ya Tekrar Transfer',

                ],
            ],
            'send-to-bank' => [
                'title'        => 'Banka\'ya transfer',
                'subtitle'     => 'Parayı Bankaya Aktar',

                'confirmation' => [
                    'title'           => 'Parayı Bankaya Aktar',
                    'send-to'         => 'Para gönderiyorsun',
                    'transfer-amount' => 'Transfer miktarı',
                    'money-send'      => 'Para başarıyla aktarıldı',
                    'send-again'      => 'Tekrar para gönderin',

                ],
            ],
            'request'      => [
                'title'        => 'Para İste',

                'confirmation' => [
                    'title'              => 'Para İste',
                    'request-money-from' => 'Sizden para talep ediyorsunuz',
                    'requested-amount'   => 'Talep edilen miktar',
                    'success'            => 'başarı',
                    'success-send'       => 'Para Talebi Başarıyla Gönderildi',
                    'request-amount'     => 'Talep Tutarı',
                    'request-again'      => 'Tekrar Para İste',

                ],
                'success'      => [
                    'title'            => 'İstek Parasını Kabul Et',
                    'request-complete' => 'İstenen Para Başarıyla Kabul Edildi',
                    'accept-amount'    => 'Kabul Edilen Tutar',

                ],
                'accept'       => [
                    'title' => 'Ödeme İsteğini Kabul Et',

                ],
            ],
            'common'       => [
                'recipient'   => 'alıcı',
                'amount'      => 'Miktar',
                'currency'    => 'Para birimi',
                'note'        => 'Not',
                'anyone-else' => 'E-postanızı asla başkalarıyla paylaşmayız.',
                'enter-note'  => 'Notu Girin',
                'enter-email' => 'E-posta Girin',

            ],
        ],
        'vouchers'      => [
            'success' => [
                'print' => 'baskı',
            ],
        ],
        'merchant'      => [

            'menu'                => [
                'merchant'      => 'tüccarlar',
                'payment'       => 'Ödemeler',
                'list'          => 'Liste',
                'details'       => 'ayrıntılar',
                'edit-merchant' => 'Satıcıyı Düzenle',
                'new-merchant'  => 'Yeni Satıcı',

            ],
            'table'               => [
                'id'            => 'İD',
                'business-name' => 'iş adı',
                'site-url'      => 'Site URL\'si',
                'type'          => 'tip',
                'status'        => 'durum',
                'action'        => 'Aksiyon',
                'not-found'     => 'Veri bulunamadı !',
                'moderation'    => 'ılımlılık',
                'disapproved'   => 'Onaylanmamış',
                'approved'      => 'onaylı',

            ],
            'html-form-generator' => [
                'title'             => 'HTML Form oluşturucu',
                'merchant-id'       => 'Tüccar kimliği',
                'item-name'         => 'Öğe adı',
                'order-number'      => 'Sipariş numarası',
                'price'             => 'Fiyat',
                'custom'            => 'görenek',
                'right-form-title'  => 'Örnek HTML formu',
                'right-form-copy'   => 'kopya',
                'right-form-copied' => 'Kopyalanan',
                'right-form-footer' => 'Form kodunu kopyalayın ve web sitenize yerleştirin.',
                'close'             => 'Kapat',
                'generate'          => 'üretmek',
                'app-info'          => 'Uygulama bilgisi',
                'client-id'         => 'Müşteri Kimliği',
                'client-secret'     => 'Müşteri sırrı',

            ],
            'payment'             => [
                'merchant'   => 'tüccar',
                'method'     => 'Yöntem',
                'order-no'   => 'Sipariş no',
                'amount'     => 'Miktar',
                'fee'        => 'ücret',
                'total'      => 'Genel Toplam',
                'currency'   => 'Para birimi',
                'status'     => 'durum',
                'created-at' => 'tarih',
                'pending'    => 'kadar',
                'success'    => 'başarı',
                'block'      => 'Blok',
                'refund'     => 'Geri ödeme',

            ],
            'add'                 => [
                'title'    => 'Satıcı oluştur',
                'name'     => 'isim',
                'site-url' => 'Site URL\'si',
                'type'     => 'tip',
                'note'     => 'Not',
                'logo'     => 'Logo',

            ],
            'details'             => [
                'merchant-id'   => 'Tüccar kimliği',
                'business-name' => 'iş adı',
                'status'        => 'durum',
                'site-url'      => 'Site URL\'si',
                'note'          => 'Not',
                'date'          => 'tarih',

            ],
            'edit'                => [
                'comment-for-administration' => 'Yönetim için yorum',

            ],
        ],
        'dispute'       => [
            'dispute'        => 'İhtilaflar',
            'title'          => 'Başlık',
            'dispute-id'     => 'Uyuşmazlık Kimliği',
            'transaction-id' => 'İşlem Kimliği',
            'created-at'     => 'At düzenlendi',
            'status'         => 'durum',
            'no-dispute'     => 'Veri bulunamadı!',
            'defendant'      => 'Sanık',
            'claimant'       => 'davacı',
            'description'    => 'Açıklama',

            'status-type'    => [
                'open'   => 'Açık',
                'solved' => 'çözülmüş',
                'closed' => 'Kapalı',
                'solve'  => 'çözmek',
                'close'  => 'Kapat',

            ],
            'discussion'     => [

                'sidebar' => [
                    'title-text'    => 'Uyuşmazlık Bilgileri',
                    'header'        => 'Uyuşmazlık Bilgileri',
                    'title'         => 'Başlık',
                    'reason'        => 'neden',
                    'change-status' => 'Durum değiştirmek',

                ],
                'form'    => [
                    'title'   => 'Anlaşmazlığı Görüntüle',
                    'message' => 'Mesaj',
                    'file'    => 'Dosya',

                ],
            ],
        ],
        'setting'       => [
            'title'                   => 'Kullanıcı profili',
            'change-avatar'           => 'Change Avatar',
            'change-avatar-here'      => 'Avatarı buradan değiştirebilirsiniz',
            'change-password'         => 'Şifre değiştir',
            'change-password-here'    => 'Şifreyi buradan değiştirebilirsiniz',
            'profile-information'     => 'profil bilgisi',
            'email'                   => 'E-posta',
            'first-name'              => 'İsim',
            'last-name'               => 'Soyadı',
            'mobile'                  => 'Telefon numarası',
            'address1'                => 'Adres 1',
            'address2'                => 'Adres 2',
            'city'                    => 'Kent',
            'state'                   => 'Belirtmek, bildirmek',
            'country'                 => 'ülke',
            'timezone'                => 'Saat dilimi',
            'old-password'            => 'eski şifre',
            'new-password'            => 'Yeni Şifre',
            'confirm-password'        => 'Şifreyi Onayla',
            'add-phone'               => 'Telefon ekle',
            'add-phone-subhead1'      => 'Tıklamak',
            'add-phone-subhead2'      => 'telefon eklemek',
            'add-phone-subheadertext' => 'Kullanmak istediğiniz numarayı girin',
            'get-code'                => 'Kodu al',
            'phone-number'            => 'Telefon numarası',
            'edit-phone'              => 'Telefonu düzenle',
            'default-wallet'          => 'Varsayılan Cüzdan',

        ],
        'ticket'        => [
            'title'     => 'Biletler',
            'ticket-no' => 'Bilet Yok',
            'subject'   => 'konu',
            'status'    => 'durum',
            'priority'  => 'öncelik',
            'date'      => 'tarih',
            'action'    => 'Aksiyon',
            'no-ticket' => 'Veri bulunamadı!',

            'add'       => [
                'title'    => 'Yeni Bilet',
                'name'     => 'isim',
                'message'  => 'Mesaj',
                'priority' => 'öncelik',

            ],
            'details'   => [

                'sidebar' => [
                    'header'    => 'Bilet Bilgileri',
                    'ticket-id' => 'Bilet kimliği',
                    'subject'   => 'konu',
                    'date'      => 'tarih',
                    'priority'  => 'öncelik',
                    'status'    => 'durum',

                ],
                'form'    => [
                    'title'   => 'Bilet Görüntüle',
                    'message' => 'Mesaj',
                    'file'    => 'Dosya',

                ],
            ],
        ],

        // Crypto
        'crypto'        => [
            'send'    => [
                'create'  => [
                    'recipient-address-input-label-text'         => 'Alıcı adresi',
                    'recipient-address-input-placeholder-text-1' => 'Geçerli bir alıcı girin',
                    'recipient-address-input-placeholder-text-2' => 'adres',
                    'address-qr-code-foot-text-1'                => 'Sadece gönder',
                    'amount-warning-text-1'                      => 'Çekilen / gönderilen miktar en az',
                    'amount-warning-text-2'                      => 'Lütfen en azından sakla',
                    'amount-warning-text-3'                      => 'ağ ücretleri için',
                    'amount-warning-text-4'                      => 'Kripto işlemlerinin tamamlanması birkaç dakika sürebilir',
                    'amount-allowed-decimal-text'                => '8 ondalık basamağa kadar izin verilir',
                ],
                'confirm' => [
                    'about-to-send-text-1' => 'Göndermek üzeresiniz',
                    'about-to-send-text-2' => 'için',
                    'sent-amount'          => 'Gönderilen Tutar',
                    'network-fee'          => 'Ağ Ücreti',
                ],
                'success' => [
                    'sent-successfully' => 'Başarıyla gönderildi',
                    'amount-added'      => 'Tutar daha sonra eklenecek',
                    'confirmations'     => 'onayları',
                    'address'           => 'Adres',
                    'again'             => 'Tekrar',
                ],
            ],
            'receive' => [
                'address-qr-code-head-text'   => 'Adres Qr Kodu Alma',
                'address-qr-code-foot-text-1' => 'Sadece al',
                'address-qr-code-foot-text-2' => 'bu adrese',
                'address-qr-code-foot-text-3' => 'başka bir bozuk para almak kalıcı zararla sonuçlanacaktır',
                'address-input-label-text'    => 'Alınan Adres',
                'address-input-copy-text'     => 'Kopya',
            ],
            'transactions' => [
                'receiver-address' => 'Alıcı adresi',
                'sender-address' => 'Gönderen adresi',
                'confirmations' => 'Onaylar',
            ],
            'preference-disabled' => 'Sistem yöneticisi kripto para birimini devre dışı bıraktı',
        ],
    ],
    'express-payment'      => [
        'payment'           => 'Ödeme',
        'pay-with'          => 'İle ödemek',
        'about-to-make'     => 'Üzerinden ödeme yapmak üzeresiniz',
        'test-payment-form' => 'Test ödeme formu',
        'pay-now'           => 'Şimdi öde!',
    ],

    'express-payment-form' => [
        'merchant-not-found'   => 'Mağaza bulunamadı! Lütfen geçerli bir satıcıyı deneyin.',
        'merchant-found'       => 'Alıcı özel bir onaylama aldı ve güvenilirliğini onayladı',
        'continue'             => 'Devam et',
        'email'                => 'E-posta',
        'password'             => 'Parola',
        'cancel'               => 'İptal etmek',
        'go-to-payment'        => 'Ödeme yap',
        'payment-agreement'    => 'Ödeme güvenli bir sayfada yapılır. Ödeme yaparken, Sözleşme Şartlarını kabul etmiş olursunuz.',
        'debit-credit-card'    => 'Kredi / Banka Kartı',
        'merchant-payment'     => 'Satıcı Ödemesi',
        'sorry'                => 'Afedersiniz!',
        'payment-unsuccessful' => 'Ödeme başarısız.',
        'success'              => 'Başarı!',                       //
        'payment-successfull'  => 'Ödeme başarıyla tamamlandı.', //
        'back-home'            => 'Eve dön',
    ],
];
