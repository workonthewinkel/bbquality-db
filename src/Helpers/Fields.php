<?php

    namespace BbqData\Helpers;


    class Fields{


        /**
         * Return all 
         *
         * @return Array
         */
        public static function all()
        {
            return [
                'first_name' => [
                    'label' => 'Voornaam',
                    'required' => true,
                    'recurring' => false,
                ],
                'last_name' => [
                    'label' => 'Achternaam',
                    'required' => true,
                    'recurring' => false,
                ],
                'email' => [
                    'label' => 'E-mailadres',
                    'required' => true,
                    'valdiate' => ['email'],
                    'recurring' => false,
                ],
                'phone' => [
                    'label' => 'Telefoonnummer',
                    'required' => false,
                    'validate' => ['phone'],
                    'recurring' => false,
                ],
                'company' => [
                    'label' => 'Bedrijfsnaam',
                    'required' => false,
                    'recurring' => false,
                ],
                'address' => [
                    'label' => 'Adres',
                    'required' => true,
                    'validate' => ['address'],
                    'recurring' => true,
                ],
                'zipcode' => [
                    'label' => 'Postcode',
                    'required' => true,
                    'validate' => ['zipcode'],
                    'recurring' => true,
                ],
                'city' => [
                    'label' => 'Stad',
                    'required' => true,
                    'recurring' => true,
                ],
                'country' => [
                    'label' => 'Land',
                    'required' => true,
                    'default' => 'Nederland',
                    'recurring' => false,
                ],
                'create_account' => [
                    'label' => 'Maak een account voor me aan',
                    'required' => false,
                    'default' => false,
                    'recurring' => false
                ],
                'password1' => [
                    'label' => 'Wachtwoord',
                    'required' => false,
                    'recurring' => false
                ],
                'password2' => [
                    'label' => 'Wachtwoord (nogmaals)',
                    'required' => false,
                    'recurring' => false
                ],
                'different_shipping' => [
                    'label' => 'Ander afleveradres?',
                    'required' => false,
                    'default' => false,
                    'recurring' => false
                ],
                'shipping_first_name' => [
                    'label' => 'Voornaam',
                    'required' => false,
                    'recurring' => false
                ],
                'shipping_last_name' => [
                    'label' => 'Achternaam',
                    'required' => false,
                    'recurring' => false
                ],
                'shipping_address' => [
                    'label' => 'Verzendadres',
                    'required' => false,
                    'validate' => ['address'],
                    'recurring' => false
                ],
                'shipping_zipcode' => [
                    'label' => 'Verzendadres: postcode',
                    'required' => false,
                    'validate' => ['zipcode'],
                    'recurring' => false
                ],
                'shipping_city' => [
                    'label' => 'Verzendadres: stad',
                    'required' => false,
                    'recurring' => false
                ],
                'shipping_country' => [
                    'label' => 'Verzendadres: Land',
                    'required' => false,
                    'recurring' => false
                ],
                'customer_remarks' => [
                    'label' => 'Opmerking',
                    'required' => false,
                    'recurring' => false
                ],
                'date_of_birth' => [
                    'label' => 'Geboortedatum',
                    'required' => false,
                    'recurring' => false
                ],
                'newsletter_signup' => [
                    'label' => 'Nieuwsbrief',
                    'required' => false,
                    'recurring' => false
                ]
            ];
        }

        
        /**
         * All field names
         *
         * @return Array
         */
        public static function names() 
        {
            return array_keys( static::all() );    
        }

        
        /**
         * Return all required fields
         *
         * @return Array
         */
        public static function required()
        {
            $fields = \collect( static::all() );
            return $fields->filter( function( $value, $key ){
                return ( $value['required'] == true );
            })->toArray();
        }

        
        /**
         * Return the defaults with fields
         *
         * @return void
         */
        public static function defaults()
        {
            $response = [];
            foreach( static::all() as $key => $field ){
                $default = $field['default'] ?? '';
                $response[ $key ] = $default;
            }

            return $response;
        }



        
        /**
         * Return all fields to keep in the request during checkout: 
         *
         * @return Array
         */
        public static function to_keep() 
        {
            $fields = static::all();
            unset( $fields['password1'] );
            unset( $fields['password2'] );

            return $fields;
        }

        /**
         * Return a field slug for errors
         *
         * @param String $string
         * @return String
         */
        public static function encode_url( $string ) 
        {
            return str_replace( '_', '-', $string );
        }

        /**
         * Decode from url
         *
         * @param String $string
         * @return String
         */
        public static function decode_url( $string ) {
            return str_replace( '-', '_', $string );
        }


        /**
         * Return shipping keys
         *
         * @return array
         */
        public static function shipping()
        {
            return ['different_shipping', 'shipping_first_name', 'shipping_last_name', 'shipping_address', 'shipping_zipcode', 'shipping_city', 'shipping_country'];
        }


        /**
         * Return recurring fields
         *
         * @return array
         */
        public static function recurring()
        {
            $fields = \collect( static::all() );
            return $fields->filter( function( $value, $key ){
                return ( $value['recurring'] == true );
            })->toArray();
        }


        /**
         * Return the newsletter labels
         *
         * @return void
         */
        public static function newsletter_labels( $key = null ) 
        {
            $labels = [
                'checkout' => __( 'Ik meld me aan voor de wekelijkse nieuwsbrief van BBQuality en ontvang graag de nieuwste producten, lekkerste recepten en acties in mijn inbox.', 'bbquality' ),
                'newsletter' => '', 
                'account' => ''
            ];

            if( is_null( $key ) ){
                return $labels;
            }

            return $labels[ $key ] ?? null;
        }

        /**
         * Return the newsletter label
         */
        public static function newsletter_label() 
        {
            return 'Ja, ik wil wekelijks leuke aanbiedingen van BBQuality ontvangen per e-mail';    
        }
    }