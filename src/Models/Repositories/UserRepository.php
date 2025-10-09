<?php
namespace App\Models\Repositories;

use App\Models\Entities\User;
use App\Core\DB;

class UserRepository
{
	public static function findByUsername($username)
	{
		$pdo = DB::pdo();
		$stmt = $pdo->prepare("
			SELECT 
				u.id, u.username, u.password_hash, 
				u.full_name, u.email, u.phone, u.gender, u.date_of_birth, 
				u.avatar_url, u.is_active, u.force_change_password,
				u.role_id,
				r.name AS role_name
			FROM users u
			LEFT JOIN roles r ON r.id = u.role_id
			WHERE u.username = ?
			LIMIT 1
		");
		$stmt->execute([$username]);
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$data)
			return null;
		$user = new User();
		foreach ($data as $k => $v) {
			if (property_exists($user, $k))
				$user->$k = $v;
		}
		$user->role_id = $data['role_id'] ?? null;
		$user->role_name = $data['role_name'] ?? null;
		return $user;
	}

	public static function findById($id)
	{
		$pdo = DB::pdo();
		$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
		$stmt->execute([$id]);
		$data = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$data)
			return null;
		$user = new User();
		foreach ($data as $k => $v) {
			if (property_exists($user, $k))
				$user->$k = $v;
		}
		return $user;
	}

	public static function updateProfile($id, $full_name, $email, $phone, $gender, $date_of_birth)
	{
		$pdo = DB::pdo();
		$stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ?, date_of_birth = ? WHERE id = ?');
		return $stmt->execute([$full_name, $email, $phone, $gender, $date_of_birth, $id]);
	}

	public static function updateAvatar($id, $avatarPath)
	{
		$pdo = DB::pdo();
		$stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
		return $stmt->execute([$avatarPath, $id]);
	}

	public static function updatePassword($id, $newHash)
	{
		$pdo = DB::pdo();
		$stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
		return $stmt->execute([$newHash, $id]);
	}
}
