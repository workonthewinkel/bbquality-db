<?php

    namespace BbqData\Helpers;

    use Carbon\Carbon;

    class Recurring{


        /**
         * Return the next delivery day
         *
         * @return void
         */
        public static function nextDeliveryDay() 
        {
            $thursday = Carbon::now()->firstOfMonth( Carbon::THURSDAY );

            if( Carbon::now()->timestamp > $thursday->timestamp ){
                $thursday = $thursday->addMonth()->firstOfMonth( Carbon::THURSDAY );
            }

            return $thursday;
        }

        /**
         * Return the last delivery day
         *
         * @return void
         */
        public static function lastDeliveryDay() 
        {
            $thursday = Carbon::now()->firstOfMonth( Carbon::THURSDAY );

            if( Carbon::now()->timestamp < $thursday->timestamp ){
                $thursday = $thursday->subMonth()->firstOfMonth( Carbon::THURSDAY );
            }

            return $thursday;
        }

        /**
         * Return the delivery day of next month        
         *
         * @return void
         */
        public static function nextMonthsDeliveryDay()
        {
            $thursday = static::nextDeliveryDay();
            return $thursday->addMonth()->firstOfMonth( Carbon::THURSDAY );
        }

    }