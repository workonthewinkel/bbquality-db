<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;

    class Affiliate extends Model
    {

        use SoftDeletes;

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'affiliates';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * Return the user connection
         *
         * @return void
         */
        public function user()
        {
            return $this->hasOne('BbqData\Models\User', 'ID', 'user_id' );    
        }


    }
