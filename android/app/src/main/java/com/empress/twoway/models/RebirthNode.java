package com.empress.twoway.models;

public class RebirthNode {
    private String memberId;
    private String sponsorId;
    private String placementParentId;
    private String name;
    private String status;

    public RebirthNode(String memberId, String sponsorId, String placementParentId, String name, String status) {
        this.memberId = memberId;
        this.sponsorId = sponsorId;
        this.placementParentId = placementParentId;
        this.name = name;
        this.status = status;
    }

    public String getMemberId() { return memberId; }
    public String getSponsorId() { return sponsorId; }
    public String getPlacementParentId() { return placementParentId; }
    public String getName() { return name; }
    public String getStatus() { return status; }
}
