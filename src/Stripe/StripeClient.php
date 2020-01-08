<?php
namespace App\Stripe;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StripeClient {
	private $em;

	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
	}

	public function createCustomer(User $user, $paymentToken){
		$customer = \Stripe\Customer::create([
			'email' => $user->getEmail(),
			'source' => $paymentToken
		]);

		$user->setStripeCustomerId($customer->id);
		$this->em->persist($user);
		$this->em->flush();

		return $customer;
	}

	public function updateCustomerCard(User $user, $paymentToken){
		$customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());
		$customer->source = $paymentToken;
		$customer->save();
	}
}