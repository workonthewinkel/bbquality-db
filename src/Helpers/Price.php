<?php

    namespace BbqOrders\Helpers;

    class Price{

        /**
         * Return the formatted price:
         *
         * @param float $price
         * @return string
         */
        public static function format( $price )
        {
            return '&euro; '.number_format( (float) $price, 2, ',', '.' );    
        }

        /**
         * Calculate vat
         *
         * @param Float $price
         * @param Float $percentage
         * @return Float
         */
        public static function vat( $price, $percentage )
        {
            $percentage = ( $percentage * 100 ) + 100;
            $exVat =  ( $price / $percentage ) * 100 ;
            $vat = $price - $exVat;

            return $vat;
        }
    }