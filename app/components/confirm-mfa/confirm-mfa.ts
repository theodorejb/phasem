import {Component} from '@angular/core';
import {ApiService} from "../../services/ApiService";
import {MfaService} from "../../services/MfaService";

@Component({
    templateUrl: 'confirm-mfa.html',
})
export class ConfirmMfaComponent {
    public error: string;
    public submitting = false;
    public code = '';

    constructor(
        private mfaService: MfaService,
        private apiService: ApiService,
    ) {}

    verifyMfa() {
        this.submitting = true;

        this.mfaService.verifyCode(this.code)
            .subscribe(
                resp => {
                    this.apiService.setAuth(resp.token, true);
                    this.apiService.defaultRedirect();
                },
                error => {this.error = error;},
            )
            .add(() => {this.submitting = false;});
    }
}
