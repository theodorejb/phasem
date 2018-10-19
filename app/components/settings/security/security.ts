import {Component, OnInit} from '@angular/core';
import {MfaStatus} from "../../../models/MFA";
import {MfaService} from "../../../services/MfaService";

@Component({
    templateUrl: 'security.html',
})
export class SecurityComponent implements OnInit {
    public error: string;
    public status: MfaStatus;

    constructor(
        private mfaService: MfaService,
    ) {}

    ngOnInit() {
        this.mfaService.getStatus().subscribe(
            status => {this.status = status;},
            error => {this.error = error;},
        );
    }
}
