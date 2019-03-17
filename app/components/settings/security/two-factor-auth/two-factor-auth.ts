import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router} from '@angular/router';
import {MfaStatus} from "../../../../models/MFA";
import {MfaService} from "../../../../services/MfaService";

@Component({
    templateUrl: 'two-factor-auth.html',
})
export class TwoFactorAuthComponent implements OnInit {
    public error: string;
    public isLoading = true;
    public specialError: string;
    public status: MfaStatus;
    public disablingMfa = false;

    constructor(
        private mfaService: MfaService,
        private route: ActivatedRoute,
        private router: Router,
    ) {}

    ngOnInit() {
        this.route.queryParamMap.subscribe(params => {
            if (params.get('no2FA')) {
                this.specialError = 'No two-factor setup found. Please attempt setup again.';
            } else {
                this.specialError = '';
            }
        });

        this.mfaService.getStatus()
            .subscribe(
                status => {this.status = status;},
                error => {this.error = error;},
            )
            .add(() => {this.isLoading = false;});
    }

    disableMfa() {
        if (!confirm('Are you sure you want to disable two-factor authentication?')) {
            return;
        }

        this.disablingMfa = true;

        this.mfaService.disableMfa().subscribe(
            () => {
                this.router.navigate(['/settings/security']);
            },
            error => {this.error = error;},
        ).add(() => {
            this.disablingMfa = false;
        });
    }
}
