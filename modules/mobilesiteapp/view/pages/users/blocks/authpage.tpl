<div class="authWrapper">
  {hook name="mobilesiteapp-blocksusers:authpage" title="{t}Мобильное приложение - авторизация: блок, авторизация{/t}"}
    {literal}
    <ion-list no-lines no-border>
      <ion-item>
        <ion-label floating>Логин</ion-label>
        <ion-input type="text" [(ngModel)]="username"></ion-input>
      </ion-item>
      <ion-item>
        <ion-label floating>Пароль</ion-label>
        <ion-input type="password" [(ngModel)]="password" (keyup.enter)="sendAuth()"></ion-input>
      </ion-item>
    </ion-list>
    {/literal}
  {/hook}

  <ion-grid>
    <ion-row>
      <ion-col col-8 offset-2>
        <button ion-button block round text-capitalize tappable (click)="sendAuth()" class="app_btn">Войти</button>
      </ion-col>
    </ion-row>
  </ion-grid>

  <!--<div class="auth_buttons" margin text-center>-->
    <!--<button ion-button round text-capitalize tappable (click)="sendAuth()">Войти</button>-->
    <!--&lt;!&ndash;<button ion-button round text-capitalize class="register_button">Регистрация</button>&ndash;&gt;-->
  <!--</div>-->
</div>
