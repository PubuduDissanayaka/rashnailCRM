<!-- Footer Start -->
<footer class="footer">
    <div class="container-fluid" data-content="">
        <div class="row">
            <div class="col-12 text-center">
                @php
                    $footerWebsite = \App\Models\Setting::get('business.website', '');
                    $footerFacebook = \App\Models\Setting::get('business.social.facebook', '');
                    $footerInstagram = \App\Models\Setting::get('business.social.instagram', '');
                    $footerTwitter = \App\Models\Setting::get('business.social.twitter', '');
                    $footerLinkedin = \App\Models\Setting::get('business.social.linkedin', '');
                @endphp

                @if($footerWebsite || $footerFacebook || $footerInstagram || $footerTwitter || $footerLinkedin)
                <div class="mb-2">
                    @if($footerWebsite)
                        <a href="{{ $footerWebsite }}" target="_blank" rel="noopener" class="text-muted me-3" title="Website">
                            <i class="ti ti-world"></i>
                        </a>
                    @endif
                    @if($footerFacebook)
                        <a href="{{ $footerFacebook }}" target="_blank" rel="noopener" class="text-muted me-3" title="Facebook">
                            <i class="ti ti-brand-facebook"></i>
                        </a>
                    @endif
                    @if($footerInstagram)
                        <a href="{{ $footerInstagram }}" target="_blank" rel="noopener" class="text-muted me-3" title="Instagram">
                            <i class="ti ti-brand-instagram"></i>
                        </a>
                    @endif
                    @if($footerTwitter)
                        <a href="{{ $footerTwitter }}" target="_blank" rel="noopener" class="text-muted me-3" title="Twitter">
                            <i class="ti ti-brand-x"></i>
                        </a>
                    @endif
                    @if($footerLinkedin)
                        <a href="{{ $footerLinkedin }}" target="_blank" rel="noopener" class="text-muted" title="LinkedIn">
                            <i class="ti ti-brand-linkedin"></i>
                        </a>
                    @endif
                </div>
                @endif

                &copy;
                <script>
                    document.write(new Date().getFullYear())
                </script> Developed By <a href="https://devtenent.com"><span class="fw-semibold">DevTenent</span></a>
            </div>
        </div>
    </div>
</footer>
<!-- end Footer -->
