<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Account extends Model
    {
        /**
         * Accounts table
         *
         * @var string
         */
        protected $table = 'accounts';

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
            'date_of_birth' => 'date',
        ];


        /**
         * An account belongs to a WordPress user
         *
         * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
         */
        public function user()
        {
            return $this->belongsTo( User::class, 'user_id', 'ID' );
        }


        /**
         * An account has many product-relevance rows
         *
         * @return \Illuminate\Database\Eloquent\Relations\HasMany
         */
        public function products()
        {
            return $this->hasMany( AccountProduct::class );
        }


        /**
         * Find the account for this WP user, or create a slim row.
         *
         * @param User|int $user
         * @return static
         */
        public static function findOrCreateFromUser( $user )
        {
            if ( ! $user instanceof User ) {
                $user = User::find( $user );
            }

            if ( is_null( $user ) ) {
                throw new \InvalidArgumentException( 'User not found' );
            }

            $userId = static::userIdFrom( $user );
            $existing = static::where( 'user_id', $userId )->first();
            if ( ! is_null( $existing ) ) {
                return $existing;
            }

            return static::create( array_merge(
                [ 'user_id' => $userId ],
                static::profileFromUser( $user )
            ) );
        }


        /**
         * WP users use `ID`; Eloquent create stores `id`.
         *
         * @param User $user
         * @return int
         */
        public static function userIdFrom( User $user )
        {
            return $user->getAttribute( 'ID' )
                ?? $user->getAttribute( 'id' )
                ?? $user->getKey();
        }


        /**
         * Best-effort profile from the user row and (in WP) usermeta.
         *
         * @param User $user
         * @return array
         */
        protected static function profileFromUser( User $user )
        {
            $userId = static::userIdFrom( $user );
            $profile = [
                'email' => $user->user_email ?? '',
                'first_name' => null,
                'last_name' => null,
                'phone' => null,
                'date_of_birth' => null,
            ];

            if ( ! function_exists( 'get_user_meta' ) ) {
                return $profile;
            }

            foreach ( [ 'first_name', 'last_name', 'phone', 'date_of_birth' ] as $key ) {
                $value = get_user_meta( $userId, $key, true );
                $profile[ $key ] = ( $value === '' || $value === false ) ? null : $value;
            }

            return $profile;
        }
    }
