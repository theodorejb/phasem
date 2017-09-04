import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../services/AuthService";

@Component({
    templateUrl: 'logout.html',
})
export class LogOutComponent implements OnInit {
    public error: string;
    public success: boolean = false;

    constructor(private authService: AuthService) {}

    ngOnInit() {
        this.authService.logOut()
            .subscribe(
                () => {this.success = true;},
                error => {this.error = error;}
            );
    }
}
