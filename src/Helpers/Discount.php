<?php

    namespace BbqData\Helpers;

    class Discount{

        /**
         * Get a single discount information
         *
         * @param string $key
         * @return array
         */
        public static function find( $key ) 
        {
            $types = static::types();
            if( isset( $types[ $key ] ) ){
                return $types[ $key ];
            }
            
            return null;
        }


        /**
         * Return all different discount types:
         *
         * @return void
         */
        public static function types() 
        {
            return [
                'sale' => [
                    'name'          => 'Aanbieding',
                    'slug'          => 'sale',
                    'quantity'      => 1,
                    'percentage'    => 'product'
                ],
                'second-half-price' => [
                    'name'          => '2e halve prijs',
                    'slug'          => 'second-half-price',
                    'quantity'      => 2,
                    'percentage'    => 50
                ]
            ];
        }



        /**
         * Calculate the discount on a discount:
         *
         * @param OrderRow $row
         * @return array
         */
        public static function calculate( $row ) 
        {
            if( is_null( $row->discount_type ) || $row->discount_type === 'sale' ){
                return 0;
            }

            $discount = static::find( $row->discount_type );
			dd( $discount );
            if( is_null( $discount ) || $row->quantity < $discount['quantity'] ){
                return 0;
            }

            $percentage = $discount['percentage'] / 100;
            $amount = (int)( $row->quantity / $discount['quantity'] );

            //calculate the straight price discount 
            return ( $amount * $row->original_price ) * $percentage;
        }

        
    }
