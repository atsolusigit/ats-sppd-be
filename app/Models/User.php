<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'status',
        'profile_img',
        'department_id',
        'jabatan_id',
        'role_id',
        'nip',
        'phone_number',
        'gender',
    ];

    /*
    |--------------------------------------------------------------------------
    | HIDE ENCRYPTED RAW DATA
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',

        // raw encrypted binary
        'name',
        'email',
        'username',
        'nip',
        'phone_number',
    ];

    /*
    |--------------------------------------------------------------------------
    | APPEND DECRYPTED ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'name_decrypted',
        'email_decrypted',
        'username_decrypted',
        'nip_decrypted',
        'phone_number_decrypted',
    ];

    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function role()
    {
        return $this->belongsTo(MstRole::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(MstDepartment::class, 'department_id');
    }

    public function jabatan()
    {
        return $this->belongsTo(MstJabatan::class, 'jabatan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | DECRYPT ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getNameDecryptedAttribute()
    {
        try {

            if (!$this->name) {
                return null;
            }

            return encrypt_decrypt_db(
                'dec',
                $this->name,
                $this->id
            );

        } catch (\Throwable $e) {

            return null;
        }
    }

    public function getEmailDecryptedAttribute()
    {
        try {

            if (!$this->email) {
                return null;
            }

            return encrypt_decrypt_db(
                'dec',
                $this->email,
                $this->id
            );

        } catch (\Throwable $e) {

            return null;
        }
    }

    public function getUsernameDecryptedAttribute()
    {
        try {

            if (!$this->username) {
                return null;
            }

            return encrypt_decrypt_db(
                'dec',
                $this->username,
                $this->id
            );

        } catch (\Throwable $e) {

            return null;
        }
    }

    public function getNipDecryptedAttribute()
    {
        try {

            if (!$this->nip) {
                return null;
            }

            return encrypt_decrypt_db(
                'dec',
                $this->nip,
                $this->id
            );

        } catch (\Throwable $e) {

            return null;
        }
    }

    public function getPhoneNumberDecryptedAttribute()
    {
        try {

            if (!$this->phone_number) {
                return null;
            }

            return encrypt_decrypt_db(
                'dec',
                $this->phone_number,
                $this->id
            );

        } catch (\Throwable $e) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION CHECK
    |--------------------------------------------------------------------------
    */

    public function hasPermission($slug)
    {
        if (!$this->role) {
            return false;
        }

        return $this->role
            ->permissions()
            ->where('slug', $slug)
            ->exists();
    }
}