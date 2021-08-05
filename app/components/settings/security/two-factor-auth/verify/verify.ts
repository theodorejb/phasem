import {Component, OnInit} from '@angular/core';
import {DomSanitizer, SafeHtml} from '@angular/platform-browser';
import {Router} from '@angular/router';
import {MfaSecret} from "../../../../../models/MFA";
import {ApiService} from "../../../../../services/ApiService";
import {MfaService} from "../../../../../services/MfaService";

@Component({
    templateUrl: 'verify.html',
})
export class VerifyComponent implements OnInit {
    public error: string;
    public isLoading = true;
    public secret: MfaSecret;
    public enablingMfa = false;
    public showSecret = false;
    public code = '';
    public qrCode: SafeHtml;

    constructor(
        private sanitizer: DomSanitizer,
        private apiService: ApiService,
        private mfaService: MfaService,
        private router: Router,
    ) {}

    ngOnInit() {
        this.mfaService.setupMfaSecret()
            .subscribe({
                next: secret => {
                    this.secret = secret;
                    this.qrCode = this.sanitizer.bypassSecurityTrustHtml(secret.qrCode);
                },
                error: error => {
                    if (error === 'No two-factor setup found. Please attempt setup again.') {
                        this.router.navigate(['/settings/security/two-factor-auth'], {queryParams: {no2FA: 1}});
                    }

                    this.error = error;
                },
            })
            .add(() => {this.isLoading = false;});
    }

    toggleSecret() {
        this.showSecret = !this.showSecret;
    }

    enableMfa() {
        this.enablingMfa = true;

        this.mfaService.enableMfa(this.code)
            .subscribe({
                next: resp => {
                    this.apiService.setAuth(resp.token, false);
                    this.router.navigate(['/settings/security']);
                },
                error: error => {this.error = error;},
            })
            .add(() => {this.enablingMfa = false;});
    }
}
