@extends('emails.layouts.premium', [
    'title' => 'Introducing Technology Solutions by Start Your Story',
    'preheader' => 'Websites, mobile apps, custom web applications and AI automation — built for modern businesses.',
])

@section('content')
    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <p class="dm-p" style="margin:0 0 14px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;color:#475569;">
        Hello {{ $name ?? 'there' }},
    </p>

    <h1 class="dm-h h1-sm"
        style="margin:0 0 14px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:28px;line-height:36px;font-weight:800;color:#0F172A;">
        Technology Solutions for Modern Businesses
    </h1>

    <p class="dm-p"
        style="margin:0 0 12px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#475569;">
        We&rsquo;re excited to introduce <strong style="color:#0F172A;" class="dm-h">Technology Solutions</strong>, a new
        service by <strong style="color:#0F172A;" class="dm-h">Start Your Story</strong>.
    </p>

    <p class="dm-p"
        style="margin:0 0 12px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#475569;">
        Beyond helping businesses hire talented professionals, we now help them build powerful digital solutions that
        improve efficiency, automate workflows and accelerate growth.
    </p>

    <p class="dm-hi"
        style="margin:0 0 26px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;font-weight:700;color:#1D4ED8;">
        Launching, modernising or automating &mdash; our experienced development team is ready to help.
    </p>

    {{-- ── Section title with flanking lines ────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
        <tr>
            <td valign="middle">
                <div class="dm-line" style="height:1px;line-height:1px;font-size:0;background-color:#E5E7EB;">&nbsp;</div>
            </td>
            <td valign="middle" width="130" align="center" style="width:130px;white-space:nowrap;padding:0 6px;">
                <span class="dm-h"
                    style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:17px;font-weight:800;color:#0F172A;">Our
                    Services</span>
            </td>
            <td valign="middle">
                <div class="dm-line" style="height:1px;line-height:1px;font-size:0;background-color:#E5E7EB;">&nbsp;</div>
            </td>
        </tr>
    </table>

    {{-- ── Service cards, row 1 (stack on mobile) ───────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 10px;">
        <tr>
            <td class="stack-card dm-card" width="49%" align="center" valign="top"
                style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="52" height="52" align="center" valign="middle"
                            style="width:52px;height:52px;background-color:#EAF1FF;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">
                            &#127760;</td>
                    </tr>
                </table>
                <p class="dm-h"
                    style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">
                    Website Development</p>
                <p class="dm-p"
                    style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">
                    Professional, responsive and SEO-optimized business websites that build credibility, generate leads
                    and deliver an exceptional user experience.</p>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="49%" align="center" valign="top"
                style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="52" height="52" align="center" valign="middle"
                            style="width:52px;height:52px;background-color:#E6F6EC;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">
                            &#128241;</td>
                    </tr>
                </table>
                <p class="dm-h"
                    style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">
                    Mobile App Development</p>
                <p class="dm-p"
                    style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">
                    Custom Android and iOS applications built with modern technologies to engage customers, streamline
                    operations and scale your business.</p>
            </td>
        </tr>
    </table>

    {{-- ── Service cards, row 2 ─────────────────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px;">
        <tr>
            <td class="stack-card dm-card" width="49%" align="center" valign="top"
                style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="52" height="52" align="center" valign="middle"
                            style="width:52px;height:52px;background-color:#FFF3DF;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">
                            &#128187;</td>
                    </tr>
                </table>
                <p class="dm-h"
                    style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">
                    Custom Web Applications</p>
                <p class="dm-p"
                    style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">
                    Tailor-made business software &mdash; ERP, CRM, client portals, dashboards and workflow management
                    designed specifically for your organisation.</p>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="49%" align="center" valign="top"
                style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="52" height="52" align="center" valign="middle"
                            style="width:52px;height:52px;background-color:#F1EAFE;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">
                            &#129302;</td>
                    </tr>
                </table>
                <p class="dm-h"
                    style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">
                    AI Automation Solutions</p>
                <p class="dm-p"
                    style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">
                    AI-powered workflows, intelligent document processing, chatbots and custom automation that save time
                    and improve productivity.</p>
            </td>
        </tr>
    </table>

    {{-- ── CTA card ─────────────────────────────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-cta"
        style="background-color:#EFF4FF;border:1px solid #DBE7FF;border-radius:12px;margin:0 0 26px;">
        <tr>
            <td class="cta-pad" style="padding:22px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="stack-col stack-gap" width="55%" valign="middle" style="padding:0 16px 0 0;">
                            <p class="dm-h"
                                style="margin:0 0 8px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:17px;font-weight:800;color:#0F172A;">
                                Have a project in mind?</p>
                            <p class="dm-p"
                                style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;line-height:19px;color:#475569;">
                                Simply reply to this email with your requirements and our team will get back to you to
                                discuss the best solution for your business.
                            </p>
                        </td>
                        <td class="stack-col" width="45%" valign="middle" align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn-block"
                                style="margin:0 auto;">
                                <tr>
                                    <td class="dm-btn" align="center" style="background-color:#1D4ED8;border-radius:9px;">
                                        <a href="{{ $ctaUrl ?? 'https://startyourstory.in/technology-solutions#contact' }}"
                                            target="_blank"
                                            style="display:inline-block;padding:13px 26px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13.5px;font-weight:700;color:#FFFFFF;">Get
                                            a Free Consultation</a>
                                    </td>
                                </tr>
                            </table>
                            <p class="dm-p"
                                style="margin:10px 0 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:10px;color:#64748B;line-height:15px;white-space:nowrap;">
                                &#128737;&#65039; No obligation &bull; Free scoping call
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── Direct contact ───────────────────────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td class="dm-card" align="center"
                style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:10px;padding:14px;">
                <p class="dm-h"
                    style="margin:0 0 4px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;">
                    Prefer email?</p>
                <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;line-height:19px;">
                    &#128231;&nbsp; <a href="mailto:info@startyourstory.in" class="dm-hi"
                        style="color:#1D4ED8;font-weight:700;">info@startyourstory.in</a>
                </p>
            </td>
        </tr>
    </table>

    {{-- ── Sign-off ─────────────────────────────────────────────────────── --}}
    <p class="dm-p"
        style="margin:0 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#475569;">
        Thank you for being a valued part of the Start Your Story community. We look forward to helping you build your
        next digital solution.
    </p>
    <p class="dm-h"
        style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;color:#0F172A;">
        Team Start Your Story
    </p>
@endsection
