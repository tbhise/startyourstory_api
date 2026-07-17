<?php

namespace App\Enums;

enum EmailPurpose: string
{
    case VERIFICATION          = 'verification';
    case WELCOME               = 'welcome';
    case FIRM_APPROVED         = 'firm_approved';
    case FIRM_REJECTED         = 'firm_rejected';
    case MESSAGE_REQUEST       = 'message_request';
    case MESSAGE_REPLY         = 'message_reply';
    case INTERVIEW_INVITE          = 'interview_invite';
    case INTERVIEW_INVITE_RESPONSE = 'interview_invite_response';
    case INTERVIEW_SCHEDULED   = 'interview_scheduled';
    case INTERVIEW_ACCEPTED    = 'interview_accepted';
    case INTERVIEW_REJECTED    = 'interview_rejected';
    case INTERVIEW_RESCHEDULE_ACCEPTED = 'interview_reschedule_accepted';
    case INTERVIEW_REMINDER_24H = 'interview_reminder_24h';
    case INTERVIEW_REMINDER_1H  = 'interview_reminder_1h';
    case APPLICATION_DIGEST    = 'application_digest';
    case CREATOR_SELECTED      = 'creator_selected';
    case CREATOR_ACCEPTED      = 'creator_accepted';
    case PASSWORD_RESET        = 'password_reset';
    case BILLING               = 'billing';
    case MARKETING             = 'marketing';
    case REENGAGEMENT          = 'reengagement';
    case REFERRAL_PAYOUT_REQUEST = 'referral_payout_request';
    case SUPPORT_TICKET_CLOSED = 'support_ticket_closed';
    case PREMIUM_ACTIVATED     = 'premium_activated';
    case INTERVIEW_RESPONSE_REMINDER = 'interview_response_reminder';
    case FIRM_APPLICANT_REMINDER     = 'firm_applicant_reminder';

    public function senderKey(): string
    {
        return match ($this) {
            self::VERIFICATION           => 'verify',
            self::WELCOME                => 'default',
            self::FIRM_APPROVED          => 'default',
            self::FIRM_REJECTED          => 'default',
            self::MESSAGE_REQUEST        => 'support',
            self::MESSAGE_REPLY          => 'support',
            self::INTERVIEW_INVITE              => 'interview',
            self::INTERVIEW_INVITE_RESPONSE     => 'interview',
            self::INTERVIEW_SCHEDULED           => 'interview',
            self::INTERVIEW_ACCEPTED            => 'interview',
            self::INTERVIEW_REJECTED            => 'interview',
            self::INTERVIEW_RESCHEDULE_ACCEPTED => 'interview',
            self::INTERVIEW_REMINDER_24H        => 'interview',
            self::INTERVIEW_REMINDER_1H         => 'interview',
            self::APPLICATION_DIGEST     => 'support',
            self::CREATOR_SELECTED       => 'default',
            self::CREATOR_ACCEPTED       => 'default',
            self::PASSWORD_RESET         => 'verify',
            self::BILLING                => 'billing',
            self::MARKETING              => 'marketing',
            self::REENGAGEMENT           => 'marketing',
            self::REFERRAL_PAYOUT_REQUEST => 'support',
            self::SUPPORT_TICKET_CLOSED  => 'support',
            self::PREMIUM_ACTIVATED      => 'billing',
            self::INTERVIEW_RESPONSE_REMINDER => 'interview',
            self::FIRM_APPLICANT_REMINDER     => 'support',
        };
    }

    public function recipientType(): string
    {
        return match ($this) {
            self::INTERVIEW_ACCEPTED,
            self::INTERVIEW_REJECTED,
            self::INTERVIEW_INVITE_RESPONSE,
            self::APPLICATION_DIGEST,
            self::FIRM_APPROVED,
            self::FIRM_REJECTED,
            self::CREATOR_ACCEPTED,
            self::FIRM_APPLICANT_REMINDER,
            self::PREMIUM_ACTIVATED      => 'firm',
            self::BILLING,
            self::MARKETING              => 'user',
            default                      => 'student',
        };
    }
}
