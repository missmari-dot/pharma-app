<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;

class SmsService
{
    private $twilio;
    private $fromNumber;

    public function __construct()
    {
        if (app()->environment('testing')) {
            $this->twilio = null;
            $this->fromNumber = null;
        } else {
            $this->twilio = new Client(
                env('TWILIO_SID'),
                env('TWILIO_AUTH_TOKEN')
            );
            $this->fromNumber = env('TWILIO_FROM_NUMBER');
        }
    }

    public function envoyerSms($numeroTelephone, $message)
    {
        if (app()->environment('testing')) {
            return [
                'success' => true,
                'sid' => 'test_sid_' . uniqid(),
                'status' => 'sent'
            ];
        }

        try {
            // Formater le numéro (ajouter +221 si nécessaire)
            $numeroFormate = $this->formaterNumero($numeroTelephone);
            
            $message = $this->twilio->messages->create(
                $numeroFormate,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );

            \Log::info('SMS envoyé avec succès', [
                'to' => $numeroFormate,
                'sid' => $message->sid,
                'status' => $message->status
            ]);

            return [
                'success' => true,
                'sid' => $message->sid,
                'status' => $message->status
            ];

        } catch (Exception $e) {
            \Log::error('Erreur envoi SMS', [
                'to' => $numeroTelephone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formaterNumero($numero)
    {
        // Nettoyer le numéro
        $numero = preg_replace('/[^0-9+]/', '', $numero);
        
        // Si le numéro commence par 77, 78, 70, 76, 75 (Sénégal)
        if (preg_match('/^(77|78|70|76|75)/', $numero)) {
            return '+221' . $numero;
        }
        
        // Si le numéro commence par 221
        if (strpos($numero, '221') === 0) {
            return '+' . $numero;
        }
        
        // Si le numéro commence déjà par +
        if (strpos($numero, '+') === 0) {
            return $numero;
        }
        
        // Par défaut, ajouter +221 (Sénégal)
        return '+221' . $numero;
    }

    public function smsReservationPrete($numeroTelephone, $codeRetrait, $nomPharmacie)
    {
        $message = "🏥 PharmaApp: Votre réservation est prête!\n\n";
        $message .= "📍 Pharmacie: {$nomPharmacie}\n";
        $message .= "🎫 Code de retrait: {$codeRetrait}\n";
        $message .= "⏰ Valable 24h\n\n";
        $message .= "Présentez ce code au pharmacien.";

        return $this->envoyerSms($numeroTelephone, $message);
    }

    public function smsOrdonnanceValidee($numeroTelephone, $nomPharmacie)
    {
        $message = "✅ PharmaApp: Ordonnance validée!\n\n";
        $message .= "📍 Pharmacie: {$nomPharmacie}\n";
        $message .= "Vos médicaments sont en cours de préparation.\n";
        $message .= "Vous recevrez un SMS quand ils seront prêts.";

        return $this->envoyerSms($numeroTelephone, $message);
    }

    public function smsOrdonnanceRejetee($numeroTelephone, $nomPharmacie, $raison = null)
    {
        $message = "❌ PharmaApp: Ordonnance rejetée\n\n";
        $message .= "📍 Pharmacie: {$nomPharmacie}\n";
        if ($raison) {
            $message .= "Raison: {$raison}\n";
        }
        $message .= "Contactez la pharmacie pour plus d'infos.";

        return $this->envoyerSms($numeroTelephone, $message);
    }
}