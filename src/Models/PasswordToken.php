<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class PasswordToken extends Model{
     
        /**
         * Expires in x hours
         */
        public const EXPIRES_IN_HOURS = 24;

        /**
         * Key table
         *
         * @var string
         */
        protected $table = 'password_tokens';

        /**
         * A password token belongs to a user
         *
         * @return BbqData\Models\User
         */
        public function user()
        {
            return $this->belongsTo( User::class );
        }
        

        /**
         * Check if the token is valid
         *
         * @return boolean
         */
        public function is_valid()
        {
            //create a timestamp, add the EXPIRES_IN_HOURS hours:
            $expires = ( self::EXPIRES_IN_HOURS * 60 * 60 ) + strtotime( $this->created_at );

            //if the current time exeeds the expires timestamp, the token is invalid
            if( time() > $expires ){
                return false;
            }
            
            //else the token is valid
            return true;
        }

    }
