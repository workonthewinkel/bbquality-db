<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class AccountProduct extends Model
    {
        /**
         * Account products table
         *
         * @var string
         */
        protected $table = 'account_products';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * @var array
         */
        protected $casts = [
            'relevance' => 'integer',
        ];


        /**
         * A row belongs to an account
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function account()
        {
            return $this->belongsTo( Account::class );
        }
    }
