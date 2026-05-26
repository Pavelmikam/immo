<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Bien;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();
        $agents  = Agent::with('user')->get();
        $biens   = Bien::where('disponible', true)->get();

        if ($clients->isEmpty() || $agents->isEmpty()) {
            return;
        }

        $echanges = [
            [
                'client_email' => 'client1@immoconnect.ci',
                'agent_email'  => 'agent1@immoconnect.ci',
                'messages'     => [
                    ['from' => 'client', 'contenu' => 'Bonjour, je suis intéressé par votre appartement au Plateau. Est-il toujours disponible ?'],
                    ['from' => 'agent',  'contenu' => 'Bonjour ! Oui, l\'appartement est toujours disponible. Quand souhaiteriez-vous le visiter ?'],
                    ['from' => 'client', 'contenu' => 'Je suis disponible ce samedi matin si cela vous convient.'],
                    ['from' => 'agent',  'contenu' => 'Parfait ! Rendez-vous samedi à 10h. Je vous envoie l\'adresse exacte.'],
                ],
            ],
            [
                'client_email' => 'client2@immoconnect.ci',
                'agent_email'  => 'agent2@immoconnect.ci',
                'messages'     => [
                    ['from' => 'client', 'contenu' => 'Bonsoir, la villa aux Deux Plateaux m\'intéresse beaucoup. Quel est le meilleur prix possible ?'],
                    ['from' => 'agent',  'contenu' => 'Bonsoir ! Le propriétaire est ouvert à la négociation. Quel budget avez-vous en tête ?'],
                    ['from' => 'client', 'contenu' => 'Je peux aller jusqu\'à 170 millions. Y a-t-il une marge de négociation ?'],
                ],
            ],
            [
                'client_email' => 'client3@immoconnect.ci',
                'agent_email'  => 'agent4@immoconnect.ci',
                'messages'     => [
                    ['from' => 'client', 'contenu' => 'Bonjour, la chambre à Yopougon est-elle meublée ? Charges comprises ?'],
                    ['from' => 'agent',  'contenu' => 'Bonjour ! Oui, entièrement meublée. Eau, électricité et wifi inclus dans le loyer.'],
                    ['from' => 'client', 'contenu' => 'Très bien ! Y a-t-il une caution ? Combien de mois à l\'avance ?'],
                    ['from' => 'agent',  'contenu' => 'Un mois de caution et un mois d\'avance. Soit 120 000 FCFA à l\'entrée.'],
                    ['from' => 'client', 'contenu' => 'D\'accord, je suis intéressé. Comment procède-t-on pour la réservation ?'],
                ],
            ],
        ];

        foreach ($echanges as $echange) {
            $client = User::where('email', $echange['client_email'])->first();
            $agent  = Agent::whereHas('user', fn ($q) => $q->where('email', $echange['agent_email']))->first();

            if (! $client || ! $agent) continue;

            $bien = $biens->filter(fn ($b) => $b->agent_id === $agent->id)->first();

            $conversation = Conversation::firstOrCreate(
                ['client_id' => $client->id, 'agent_id' => $agent->id, 'bien_id' => optional($bien)->id],
                ['statut' => 'ouverte']
            );

            foreach ($echange['messages'] as $msg) {
                $sender = $msg['from'] === 'client' ? $client : $agent->user;
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => $sender->id,
                    'contenu'         => $msg['contenu'],
                    'lu'              => true,
                ]);
            }
        }

        // Conversations supplémentaires aléatoires
        $clients->random(min(5, $clients->count()))->each(function (User $client) use ($agents, $biens) {
            $agent = $agents->random();
            if ($agent->user_id === $client->id) return;

            $bien = $biens->filter(fn ($b) => $b->agent_id === $agent->id)->first();

            $conv = Conversation::firstOrCreate(
                ['client_id' => $client->id, 'agent_id' => $agent->id, 'bien_id' => optional($bien)->id],
                ['statut' => 'ouverte']
            );

            // 2 à 5 messages alternés
            $lastSender = $client;
            for ($i = 0; $i < rand(2, 5); $i++) {
                Message::factory()->create([
                    'conversation_id' => $conv->id,
                    'sender_id'       => $lastSender->id,
                ]);
                $lastSender = ($lastSender->id === $client->id) ? $agent->user : $client;
            }
        });
    }
}
