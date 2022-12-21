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
                $thursday = Carbon::now()->addMonth()->firstOfMonth( Carbon::THURSDAY );
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
                $thursday = Carbon::now()->subMonth()->firstOfMonth( Carbon::THURSDAY );
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
            $thurs = static::nextDeliveryDay();
            return $thurs->addMonth()->firstOfMonth( Carbon::THURSDAY );
        }

    }